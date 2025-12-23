(function($) {
  'use strict';

  $(document).ready(function() {
    if ($('.customers-manager-pn-password-checker').length) {
      var pass_view_state = false;

      function customers_manager_pn_pass_check_strength(pass) {
        var strength = 0;
        var password = $('.customers-manager-pn-password-strength');
        var low_upper_case = password.closest('.customers-manager-pn-password-checker').find('.low-upper-case i');
        var number = password.closest('.customers-manager-pn-password-checker').find('.one-number i');
        var special_char = password.closest('.customers-manager-pn-password-checker').find('.one-special-char i');
        var eight_chars = password.closest('.customers-manager-pn-password-checker').find('.eight-character i');

        //If pass contains both lower and uppercase characters
        if (pass.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) {
          strength += 1;
          low_upper_case.text('task_alt');
        } else {
          low_upper_case.text('radio_button_unchecked');
        }

        //If it has numbers and characters
        if (pass.match(/([0-9])/)) {
          strength += 1;
          number.text('task_alt');
        } else {
          number.text('radio_button_unchecked');
        }

        //If it has one special character
        if (pass.match(/([!,%,&,@,#,$,^,*,?,_,~,|,¬,+,ç,-,€])/)) {
          strength += 1;
          special_char.text('task_alt');
        } else {
          special_char.text('radio_button_unchecked');
        }

        //If pass is greater than 7
        if (pass.length > 7) {
          strength += 1;
          eight_chars.text('task_alt');
        } else {
          eight_chars.text('radio_button_unchecked');
        }

        // If value is less than 2
        if (strength < 2) {
          $('.customers-manager-pn-password-strength-bar').removeClass('customers-manager-pn-progress-bar-warning customers-manager-pn-progress-bar-success').addClass('customers-manager-pn-progress-bar-danger').css('width', '10%');
        } else if (strength == 3) {
          $('.customers-manager-pn-password-strength-bar').removeClass('customers-manager-pn-progress-bar-success customers-manager-pn-progress-bar-danger').addClass('customers-manager-pn-progress-bar-warning').css('width', '60%');
        } else if (strength == 4) {
          $('.customers-manager-pn-password-strength-bar').removeClass('customers-manager-pn-progress-bar-warning customers-manager-pn-progress-bar-danger').addClass('customers-manager-pn-progress-bar-success').css('width', '100%');
        }
      }

      $(document).on('click', '.customers-manager-pn-show-pass', function(e){
        e.preventDefault();
        var customers_manager_pn_btn = $(this);
        var password_input = customers_manager_pn_btn.siblings('.customers-manager-pn-password-strength');

        if (pass_view_state) {
          password_input.attr('type', 'password');
          customers_manager_pn_btn.find('i').text('visibility');
          pass_view_state = false;
        } else {
          password_input.attr('type', 'text');
          customers_manager_pn_btn.find('i').text('visibility_off');
          pass_view_state = true;
        }
      });

      $(document).on('keyup', ('.customers-manager-pn-password-strength'), function(e){
        customers_manager_pn_pass_check_strength($('.customers-manager-pn-password-strength').val());

        if (!$('#customers-manager-pn-popover-pass').is(':visible')) {
          $('#customers-manager-pn-popover-pass').fadeIn('slow');
        }

        if (!$('.customers-manager-pn-show-pass').is(':visible')) {
          $('.customers-manager-pn-show-pass').fadeIn('slow');
        }
      });
    }
    
    $(document).on('mouseover', '.customers-manager-pn-input-star', function(e){
      if (!$(this).closest('.customers-manager-pn-input-stars').hasClass('clicked')) {
        $(this).text('star');
        $(this).prevAll('.customers-manager-pn-input-star').text('star');
      }
    });

    $(document).on('mouseout', '.customers-manager-pn-input-stars', function(e){
      if (!$(this).hasClass('clicked')) {
        $(this).find('.customers-manager-pn-input-star').text('star_outlined');
      }
    });

    $(document).on('click', '.customers-manager-pn-input-star', function(e){
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      $(this).closest('.customers-manager-pn-input-stars').addClass('clicked');
      $(this).closest('.customers-manager-pn-input-stars').find('.customers-manager-pn-input-star').text('star_outlined');
      $(this).text('star');
      $(this).prevAll('.customers-manager-pn-input-star').text('star');
      $(this).closest('.customers-manager-pn-input-stars').siblings('.customers-manager-pn-input-hidden-stars').val($(this).prevAll('.customers-manager-pn-input-star').length + 1);
    });

    $(document).on('change', '.customers-manager-pn-input-hidden-stars', function(e){
      $(this).siblings('.customers-manager-pn-input-stars').find('.customers-manager-pn-input-star').text('star_outlined');
      $(this).siblings('.customers-manager-pn-input-stars').find('.customers-manager-pn-input-star').slice(0, $(this).val()).text('star');
    });

    if ($('.customers-manager-pn-field[data-customers-manager-pn-parent]').length) {
      cm_pn_form_update();

      $(document).on('change', '.customers-manager-pn-field[data-customers-manager-pn-parent~="this"]', function(e) {
        cm_pn_form_update();
      });
    }

    if ($('.customers-manager-pn-html-multi-group').length) {
      $(document).on('click', '.customers-manager-pn-html-multi-remove-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var customers_manager_pn_users_btn = $(this);

        if (customers_manager_pn_users_btn.closest('.customers-manager-pn-html-multi-wrapper').find('.customers-manager-pn-html-multi-group').length > 1) {
          $(this).closest('.customers-manager-pn-html-multi-group').remove();
        } else {
          $(this).closest('.customers-manager-pn-html-multi-group').find('input, select, textarea').val('');
        }
      });

      $(document).on('click', '.customers-manager-pn-html-multi-add-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        $(this).closest('.customers-manager-pn-html-multi-wrapper').find('.customers-manager-pn-html-multi-group:first').clone().insertAfter($(this).closest('.customers-manager-pn-html-multi-wrapper').find('.customers-manager-pn-html-multi-group:last'));
        $(this).closest('.customers-manager-pn-html-multi-wrapper').find('.customers-manager-pn-html-multi-group:last').find('input, select, textarea').val('');

        $(this).closest('.customers-manager-pn-html-multi-wrapper').find('.customers-manager-pn-input-range').each(function(index, element) {
          $(this).siblings('.customers-manager-pn-input-range-output').html($(this).val());
        });
      });

      $('.customers-manager-pn-html-multi-wrapper').sortable({handle: '.customers-manager-pn-multi-sorting'});

      $(document).on('sortstop', '.customers-manager-pn-html-multi-wrapper', function(event, ui){
        customers_manager_pn_get_main_message(customers_manager_pn_i18n.ordered_element);
      });
    }

    if ($('.customers-manager-pn-input-range').length) {
      $('.customers-manager-pn-input-range').each(function(index, element) {
        $(this).siblings('.customers-manager-pn-input-range-output').html($(this).val());
      });

      $(document).on('input', '.customers-manager-pn-input-range', function(e) {
        $(this).siblings('.customers-manager-pn-input-range-output').html($(this).val());
      });
    }

    if ($('.customers-manager-pn-image-btn').length) {
      var image_frame;

      $(document).on('click', '.customers-manager-pn-image-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (image_frame){
          image_frame.open();
          return;
        }

        var customers_manager_pn_input_btn = $(this);
        var customers_manager_pn_images_block = customers_manager_pn_input_btn.closest('.customers-manager-pn-images-block').find('.customers-manager-pn-images');
        var customers_manager_pn_images_input = customers_manager_pn_input_btn.closest('.customers-manager-pn-images-block').find('.customers-manager-pn-image-input');

        var image_frame = wp.media({
          title: (customers_manager_pn_images_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_images : customers_manager_pn_i18n.select_image,
          library: {
            type: 'image'
          },
          multiple: (customers_manager_pn_images_block.attr('data-customers-manager-pn-multiple') == 'true') ? 'true' : 'false',
        });

        image_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (customers_manager_pn_images_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.edit_images : customers_manager_pn_i18n.edit_image,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(image_frame.options.library),
            multiple: (customers_manager_pn_images_block.attr('data-customers-manager-pn-multiple') == 'true') ? 'true' : 'false',
            editable: true,
            allowLocalEdits: true,
            displaySettings: true,
            displayUserSettings: true
          })
        ]);

        image_frame.open();

        image_frame.on('select', function() {
          var ids = [];
          var attachments_arr = [];

          attachments_arr = image_frame.state().get('selection').toJSON();
          customers_manager_pn_images_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            customers_manager_pn_images_block.append('<img src="' + $(this)[0].url + '" class="">');
          });

          customers_manager_pn_input_btn.text((customers_manager_pn_images_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_images : customers_manager_pn_i18n.select_image);
          customers_manager_pn_images_input.val(ids);
        });
      });
    }

    if ($('.customers-manager-pn-audio-btn').length) {
      var audio_frame;

      $(document).on('click', '.customers-manager-pn-audio-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (audio_frame){
          audio_frame.open();
          return;
        }

        var customers_manager_pn_input_btn = $(this);
        var customers_manager_pn_audios_block = customers_manager_pn_input_btn.closest('.customers-manager-pn-audios-block').find('.customers-manager-pn-audios');
        var customers_manager_pn_audios_input = customers_manager_pn_input_btn.closest('.customers-manager-pn-audios-block').find('.customers-manager-pn-audio-input');

        var audio_frame = wp.media({
          title: (customers_manager_pn_audios_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_audios : customers_manager_pn_i18n.select_audio,
          library : {
            type : 'audio'
          },
          multiple: (customers_manager_pn_audios_block.attr('data-customers-manager-pn-multiple') == 'true') ? 'true' : 'false',
        });

        audio_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (customers_manager_pn_audios_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_audios : customers_manager_pn_i18n.select_audio,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(audio_frame.options.library),
            multiple: (customers_manager_pn_audios_block.attr('data-customers-manager-pn-multiple') == 'true') ? 'true' : 'false',
            editable: true,
            allowLocalEdits: true,
            displaySettings: true,
            displayUserSettings: true
          })
        ]);

        audio_frame.open();

        audio_frame.on('select', function() {
          var ids = [];
          var attachments_arr = [];

          attachments_arr = audio_frame.state().get('selection').toJSON();
          customers_manager_pn_audios_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            customers_manager_pn_audios_block.append('<div class="customers-manager-pn-audio customers-manager-pn-tooltip" title="' + $(this)[0].title + '"><i class="dashicons dashicons-media-audio"></i></div>');
          });

          $('.customers-manager-pn-tooltip').tooltipster({maxWidth: 300,delayTouch:[0, 4000], customClass: 'customers-manager-pn-tooltip'});
          customers_manager_pn_input_btn.text((customers_manager_pn_audios_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_audios : customers_manager_pn_i18n.select_audio);
          customers_manager_pn_audios_input.val(ids);
        });
      });
    }

    if ($('.customers-manager-pn-video-btn').length) {
      var video_frame;

      $(document).on('click', '.customers-manager-pn-video-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (video_frame){
          video_frame.open();
          return;
        }

        var customers_manager_pn_input_btn = $(this);
        var customers_manager_pn_videos_block = customers_manager_pn_input_btn.closest('.customers-manager-pn-videos-block').find('.customers-manager-pn-videos');
        var customers_manager_pn_videos_input = customers_manager_pn_input_btn.closest('.customers-manager-pn-videos-block').find('.customers-manager-pn-video-input');

        var video_frame = wp.media({
          title: (customers_manager_pn_videos_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_videos : customers_manager_pn_i18n.select_video,
          library : {
            type : 'video'
          },
          multiple: (customers_manager_pn_videos_block.attr('data-customers-manager-pn-multiple') == 'true') ? 'true' : 'false',
        });

        video_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (customers_manager_pn_videos_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_videos : customers_manager_pn_i18n.select_video,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(video_frame.options.library),
            multiple: (customers_manager_pn_videos_block.attr('data-customers-manager-pn-multiple') == 'true') ? 'true' : 'false',
            editable: true,
            allowLocalEdits: true,
            displaySettings: true,
            displayUserSettings: true
          })
        ]);

        video_frame.open();

        video_frame.on('select', function() {
          var ids = [];
          var attachments_arr = [];

          attachments_arr = video_frame.state().get('selection').toJSON();
          customers_manager_pn_videos_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            customers_manager_pn_videos_block.append('<div class="customers-manager-pn-video customers-manager-pn-tooltip" title="' + $(this)[0].title + '"><i class="dashicons dashicons-media-video"></i></div>');
          });

          $('.customers-manager-pn-tooltip').tooltipster({maxWidth: 300,delayTouch:[0, 4000], customClass: 'customers-manager-pn-tooltip'});
          customers_manager_pn_input_btn.text((customers_manager_pn_videos_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_videos : customers_manager_pn_i18n.select_video);
          customers_manager_pn_videos_input.val(ids);
        });
      });
    }

    if ($('.customers-manager-pn-file-btn').length) {
      var file_frame;

      $(document).on('click', '.customers-manager-pn-file-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (file_frame){
          file_frame.open();
          return;
        }

        var customers_manager_pn_input_btn = $(this);
        var customers_manager_pn_files_block = customers_manager_pn_input_btn.closest('.customers-manager-pn-files-block').find('.customers-manager-pn-files');
        var customers_manager_pn_files_input = customers_manager_pn_input_btn.closest('.customers-manager-pn-files-block').find('.customers-manager-pn-file-input');

        var file_frame = wp.media({
          title: (customers_manager_pn_files_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_files : customers_manager_pn_i18n.select_file,
          multiple: (customers_manager_pn_files_block.attr('data-customers-manager-pn-multiple') == 'true') ? 'true' : 'false',
        });

        file_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (customers_manager_pn_files_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.select_files : customers_manager_pn_i18n.select_file,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(file_frame.options.library),
            multiple: (customers_manager_pn_files_block.attr('data-customers-manager-pn-multiple') == 'true') ? 'true' : 'false',
            editable: true,
            allowLocalEdits: true,
            displaySettings: true,
            displayUserSettings: true
          })
        ]);

        file_frame.open();

        file_frame.on('select', function() {
          var ids = [];
          var attachments_arr = [];

          attachments_arr = file_frame.state().get('selection').toJSON();
          customers_manager_pn_files_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            customers_manager_pn_files_block.append('<embed src="' + $(this)[0].url + '" type="application/pdf" class="customers-manager-pn-embed-file"/>');
          });

          customers_manager_pn_input_btn.text((customers_manager_pn_files_block.attr('data-customers-manager-pn-multiple') == 'true') ? customers_manager_pn_i18n.edit_files : customers_manager_pn_i18n.edit_file);
          customers_manager_pn_files_input.val(ids);
        });
      });
    }

    // CPT SEARCH FUNCTIONALITY
    if (typeof customers_manager_pn_cpts !== 'undefined') {
      // Initialize search functionality for each CPT
      Object.keys(customers_manager_pn_cpts).forEach(function(cptKey) {
        var cptName = customers_manager_pn_cpts[cptKey];
        var searchToggleSelector = '.customers-manager-pn-' + cptKey + '-search-toggle';
        var searchInputSelector = '.customers-manager-pn-' + cptKey + '-search-input';
        var searchWrapperSelector = '.customers-manager-pn-' + cptKey + '-search-wrapper';
        var listSelector = '.customers-manager-pn-customers_manager_pn_' + cptKey + '-list';
        var listWrapperSelector = '.customers-manager-pn-customers_manager_pn_' + cptKey + '-list-wrapper';
        var addNewSelector = '.customers-manager-pn-' + cptKey + '[data-customers_manager_pn_' + cptKey + '-id="0"]';

        // Only initialize if elements exist
        if ($(searchToggleSelector).length) {
          
          // Toggle search input visibility
          $(document).on('click', searchToggleSelector, function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var searchToggle = $(this);
            var searchInput = searchToggle.siblings(searchInputSelector);
            var searchWrapper = searchToggle.closest(searchWrapperSelector);
            var list = searchToggle.closest(listSelector);
            var listWrapper = list.find(listWrapperSelector);
            var itemsList = listWrapper.find('ul');

            if (searchInput.hasClass('customers-manager-pn-display-none')) {
              // Show search input
              searchInput.removeClass('customers-manager-pn-display-none').focus();
              searchToggle.text('close');
              searchWrapper.addClass('customers-manager-pn-search-active');
            } else {
              // Hide search input and clear filter
              searchInput.addClass('customers-manager-pn-display-none').val('');
              searchToggle.text('search');
              searchWrapper.removeClass('customers-manager-pn-search-active');
              
              // Show all items
              itemsList.find('li').show();
            }
          });

          // Filter items on keyup
          $(document).on('keyup', searchInputSelector, function(e) {
            var searchInput = $(this);
            var searchTerm = searchInput.val().toLowerCase().trim();
            var list = searchInput.closest(listSelector);
            var listWrapper = list.find(listWrapperSelector);
            var itemsList = listWrapper.find('ul');
            var items = itemsList.find('li:not(' + addNewSelector + ')');

            if (searchTerm === '') {
              // Show all items when search is empty
              items.show();
              // Also show the "Add new" item
              itemsList.find(addNewSelector).show();
            } else {
              // Filter items based on title
              items.each(function() {
                var itemTitle = $(this).find('.customers-manager-pn-display-inline-table a span').first().text().toLowerCase();
                if (itemTitle.includes(searchTerm)) {
                  $(this).show();
                } else {
                  $(this).hide();
                }
              });
              // Hide the "Add new" item when filtering
              itemsList.find(addNewSelector).hide();
            }
          });

          // Close search on escape key
          $(document).on('keydown', searchInputSelector, function(e) {
            if (e.keyCode === 27) { // Escape key
              var searchInput = $(this);
              var searchToggle = searchInput.siblings(searchToggleSelector);
              var searchWrapper = searchInput.closest(searchWrapperSelector);
              var list = searchInput.closest(listSelector);
              var listWrapper = list.find(listWrapperSelector);
              var itemsList = listWrapper.find('ul');

              searchInput.addClass('customers-manager-pn-display-none').val('');
              searchToggle.text('search');
              searchWrapper.removeClass('customers-manager-pn-search-active');
              
              // Show all items
              itemsList.find('li').show();
            }
          });
                }
      });

      // Single unified click outside handler for all search wrappers
      $(document).on('click', function(e) {
        var clickedInsideSearch = false;
        var activeSearchInput = null;
        var activeSearchToggle = null;
        var activeSearchWrapper = null;
        var activeList = null;
        var activeListWrapper = null;
        var activeItemsList = null;

        // Check if clicked inside any search wrapper
        Object.keys(customers_manager_pn_cpts).forEach(function(cptKey) {
          var searchWrapperSelector = '.customers-manager-pn-' + cptKey + '-search-wrapper';
          var searchInputSelector = '.customers-manager-pn-' + cptKey + '-search-input';
          var searchToggleSelector = '.customers-manager-pn-' + cptKey + '-search-toggle';
          var listSelector = '.customers-manager-pn-customers_manager_pn_' + cptKey + '-list';
          var listWrapperSelector = '.customers-manager-pn-customers_manager_pn_' + cptKey + '-list-wrapper';

          if ($(e.target).closest(searchWrapperSelector).length) {
            clickedInsideSearch = true;
          }

          // Find active search input
          var searchInput = $(searchInputSelector + ':not(.customers-manager-pn-display-none)');
          if (searchInput.length && !activeSearchInput) {
            activeSearchInput = searchInput;
            activeSearchToggle = searchInput.siblings(searchToggleSelector);
            activeSearchWrapper = searchInput.closest(searchWrapperSelector);
            activeList = searchInput.closest(listSelector);
            activeListWrapper = activeList.find(listWrapperSelector);
            activeItemsList = activeListWrapper.find('ul');
          }
        });

        // Close search if clicked outside
        if (!clickedInsideSearch && activeSearchInput) {
          activeSearchInput.addClass('customers-manager-pn-display-none').val('');
          activeSearchToggle.text('search');
          activeSearchWrapper.removeClass('customers-manager-pn-search-active');
          
          // Show all items
          activeItemsList.find('li').show();
        }
      });
    }
  });

  $(document).on('click', '.customers-manager-pn-toggle', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    var customers_manager_pn_toggle = $(this);

    if (customers_manager_pn_toggle.find('i').length) {
      if (customers_manager_pn_toggle.siblings('.customers-manager-pn-toggle-content').is(':visible')) {
        customers_manager_pn_toggle.find('i').text('add');
      } else {
        customers_manager_pn_toggle.find('i').text('clear');
      }
    }

    customers_manager_pn_toggle.siblings('.customers-manager-pn-toggle-content').fadeToggle();
  });
})(jQuery);
