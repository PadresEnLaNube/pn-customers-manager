(function($) {
  'use strict';

  $(document).ready(function() {
    if ($('.crmpn-password-checker').length) {
      var pass_view_state = false;

      function crmpn_pass_check_strength(pass) {
        var strength = 0;
        var password = $('.crmpn-password-strength');
        var low_upper_case = password.closest('.crmpn-password-checker').find('.low-upper-case i');
        var number = password.closest('.crmpn-password-checker').find('.one-number i');
        var special_char = password.closest('.crmpn-password-checker').find('.one-special-char i');
        var eight_chars = password.closest('.crmpn-password-checker').find('.eight-character i');

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
          $('.crmpn-password-strength-bar').removeClass('crmpn-progress-bar-warning crmpn-progress-bar-success').addClass('crmpn-progress-bar-danger').css('width', '10%');
        } else if (strength == 3) {
          $('.crmpn-password-strength-bar').removeClass('crmpn-progress-bar-success crmpn-progress-bar-danger').addClass('crmpn-progress-bar-warning').css('width', '60%');
        } else if (strength == 4) {
          $('.crmpn-password-strength-bar').removeClass('crmpn-progress-bar-warning crmpn-progress-bar-danger').addClass('crmpn-progress-bar-success').css('width', '100%');
        }
      }

      $(document).on('click', '.crmpn-show-pass', function(e){
        e.preventDefault();
        var crmpn_btn = $(this);
        var password_input = crmpn_btn.siblings('.crmpn-password-strength');

        if (pass_view_state) {
          password_input.attr('type', 'password');
          crmpn_btn.find('i').text('visibility');
          pass_view_state = false;
        } else {
          password_input.attr('type', 'text');
          crmpn_btn.find('i').text('visibility_off');
          pass_view_state = true;
        }
      });

      $(document).on('keyup', ('.crmpn-password-strength'), function(e){
        crmpn_pass_check_strength($('.crmpn-password-strength').val());

        if (!$('#crmpn-popover-pass').is(':visible')) {
          $('#crmpn-popover-pass').fadeIn('slow');
        }

        if (!$('.crmpn-show-pass').is(':visible')) {
          $('.crmpn-show-pass').fadeIn('slow');
        }
      });
    }
    
    $(document).on('mouseover', '.crmpn-input-star', function(e){
      if (!$(this).closest('.crmpn-input-stars').hasClass('clicked')) {
        $(this).text('star');
        $(this).prevAll('.crmpn-input-star').text('star');
      }
    });

    $(document).on('mouseout', '.crmpn-input-stars', function(e){
      if (!$(this).hasClass('clicked')) {
        $(this).find('.crmpn-input-star').text('star_outlined');
      }
    });

    $(document).on('click', '.crmpn-input-star', function(e){
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      $(this).closest('.crmpn-input-stars').addClass('clicked');
      $(this).closest('.crmpn-input-stars').find('.crmpn-input-star').text('star_outlined');
      $(this).text('star');
      $(this).prevAll('.crmpn-input-star').text('star');
      $(this).closest('.crmpn-input-stars').siblings('.crmpn-input-hidden-stars').val($(this).prevAll('.crmpn-input-star').length + 1);
    });

    $(document).on('change', '.crmpn-input-hidden-stars', function(e){
      $(this).siblings('.crmpn-input-stars').find('.crmpn-input-star').text('star_outlined');
      $(this).siblings('.crmpn-input-stars').find('.crmpn-input-star').slice(0, $(this).val()).text('star');
    });

    if ($('.crmpn-field[data-crmpn-parent]').length) {
      crmpn_form_update();

      $(document).on('change', '.crmpn-field[data-crmpn-parent~="this"]', function(e) {
        crmpn_form_update();
      });
    }

    if ($('.crmpn-html-multi-group').length) {
      $(document).on('click', '.crmpn-html-multi-remove-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var crmpn_users_btn = $(this);

        if (crmpn_users_btn.closest('.crmpn-html-multi-wrapper').find('.crmpn-html-multi-group').length > 1) {
          $(this).closest('.crmpn-html-multi-group').remove();
        } else {
          $(this).closest('.crmpn-html-multi-group').find('input, select, textarea').val('');
        }
      });

      $(document).on('click', '.crmpn-html-multi-add-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        $(this).closest('.crmpn-html-multi-wrapper').find('.crmpn-html-multi-group:first').clone().insertAfter($(this).closest('.crmpn-html-multi-wrapper').find('.crmpn-html-multi-group:last'));
        $(this).closest('.crmpn-html-multi-wrapper').find('.crmpn-html-multi-group:last').find('input, select, textarea').val('');

        $(this).closest('.crmpn-html-multi-wrapper').find('.crmpn-input-range').each(function(index, element) {
          $(this).siblings('.crmpn-input-range-output').html($(this).val());
        });
      });

      $('.crmpn-html-multi-wrapper').sortable({handle: '.crmpn-multi-sorting'});

      $(document).on('sortstop', '.crmpn-html-multi-wrapper', function(event, ui){
        crmpn_get_main_message(crmpn_i18n.ordered_element);
      });
    }

    if ($('.crmpn-input-range').length) {
      $('.crmpn-input-range').each(function(index, element) {
        $(this).siblings('.crmpn-input-range-output').html($(this).val());
      });

      $(document).on('input', '.crmpn-input-range', function(e) {
        $(this).siblings('.crmpn-input-range-output').html($(this).val());
      });
    }

    if ($('.crmpn-image-btn').length) {
      var image_frame;

      $(document).on('click', '.crmpn-image-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (image_frame){
          image_frame.open();
          return;
        }

        var crmpn_input_btn = $(this);
        var crmpn_images_block = crmpn_input_btn.closest('.crmpn-images-block').find('.crmpn-images');
        var crmpn_images_input = crmpn_input_btn.closest('.crmpn-images-block').find('.crmpn-image-input');

        var image_frame = wp.media({
          title: (crmpn_images_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_images : crmpn_i18n.select_image,
          library: {
            type: 'image'
          },
          multiple: (crmpn_images_block.attr('data-crmpn-multiple') == 'true') ? 'true' : 'false',
        });

        image_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (crmpn_images_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.edit_images : crmpn_i18n.edit_image,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(image_frame.options.library),
            multiple: (crmpn_images_block.attr('data-crmpn-multiple') == 'true') ? 'true' : 'false',
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
          crmpn_images_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            crmpn_images_block.append('<img src="' + $(this)[0].url + '" class="">');
          });

          crmpn_input_btn.text((crmpn_images_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_images : crmpn_i18n.select_image);
          crmpn_images_input.val(ids);
        });
      });
    }

    if ($('.crmpn-audio-btn').length) {
      var audio_frame;

      $(document).on('click', '.crmpn-audio-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (audio_frame){
          audio_frame.open();
          return;
        }

        var crmpn_input_btn = $(this);
        var crmpn_audios_block = crmpn_input_btn.closest('.crmpn-audios-block').find('.crmpn-audios');
        var crmpn_audios_input = crmpn_input_btn.closest('.crmpn-audios-block').find('.crmpn-audio-input');

        var audio_frame = wp.media({
          title: (crmpn_audios_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_audios : crmpn_i18n.select_audio,
          library : {
            type : 'audio'
          },
          multiple: (crmpn_audios_block.attr('data-crmpn-multiple') == 'true') ? 'true' : 'false',
        });

        audio_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (crmpn_audios_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_audios : crmpn_i18n.select_audio,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(audio_frame.options.library),
            multiple: (crmpn_audios_block.attr('data-crmpn-multiple') == 'true') ? 'true' : 'false',
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
          crmpn_audios_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            crmpn_audios_block.append('<div class="crmpn-audio crmpn-tooltip" title="' + $(this)[0].title + '"><i class="dashicons dashicons-media-audio"></i></div>');
          });

          $('.crmpn-tooltip').tooltipster({maxWidth: 300,delayTouch:[0, 4000], customClass: 'crmpn-tooltip'});
          crmpn_input_btn.text((crmpn_audios_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_audios : crmpn_i18n.select_audio);
          crmpn_audios_input.val(ids);
        });
      });
    }

    if ($('.crmpn-video-btn').length) {
      var video_frame;

      $(document).on('click', '.crmpn-video-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (video_frame){
          video_frame.open();
          return;
        }

        var crmpn_input_btn = $(this);
        var crmpn_videos_block = crmpn_input_btn.closest('.crmpn-videos-block').find('.crmpn-videos');
        var crmpn_videos_input = crmpn_input_btn.closest('.crmpn-videos-block').find('.crmpn-video-input');

        var video_frame = wp.media({
          title: (crmpn_videos_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_videos : crmpn_i18n.select_video,
          library : {
            type : 'video'
          },
          multiple: (crmpn_videos_block.attr('data-crmpn-multiple') == 'true') ? 'true' : 'false',
        });

        video_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (crmpn_videos_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_videos : crmpn_i18n.select_video,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(video_frame.options.library),
            multiple: (crmpn_videos_block.attr('data-crmpn-multiple') == 'true') ? 'true' : 'false',
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
          crmpn_videos_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            crmpn_videos_block.append('<div class="crmpn-video crmpn-tooltip" title="' + $(this)[0].title + '"><i class="dashicons dashicons-media-video"></i></div>');
          });

          $('.crmpn-tooltip').tooltipster({maxWidth: 300,delayTouch:[0, 4000], customClass: 'crmpn-tooltip'});
          crmpn_input_btn.text((crmpn_videos_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_videos : crmpn_i18n.select_video);
          crmpn_videos_input.val(ids);
        });
      });
    }

    if ($('.crmpn-file-btn').length) {
      var file_frame;

      $(document).on('click', '.crmpn-file-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (file_frame){
          file_frame.open();
          return;
        }

        var crmpn_input_btn = $(this);
        var crmpn_files_block = crmpn_input_btn.closest('.crmpn-files-block').find('.crmpn-files');
        var crmpn_files_input = crmpn_input_btn.closest('.crmpn-files-block').find('.crmpn-file-input');

        var file_frame = wp.media({
          title: (crmpn_files_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_files : crmpn_i18n.select_file,
          multiple: (crmpn_files_block.attr('data-crmpn-multiple') == 'true') ? 'true' : 'false',
        });

        file_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (crmpn_files_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.select_files : crmpn_i18n.select_file,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(file_frame.options.library),
            multiple: (crmpn_files_block.attr('data-crmpn-multiple') == 'true') ? 'true' : 'false',
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
          crmpn_files_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            crmpn_files_block.append('<embed src="' + $(this)[0].url + '" type="application/pdf" class="crmpn-embed-file"/>');
          });

          crmpn_input_btn.text((crmpn_files_block.attr('data-crmpn-multiple') == 'true') ? crmpn_i18n.edit_files : crmpn_i18n.edit_file);
          crmpn_files_input.val(ids);
        });
      });
    }

    // CPT SEARCH FUNCTIONALITY
    if (typeof crmpn_cpts !== 'undefined') {
      // Initialize search functionality for each CPT
      Object.keys(crmpn_cpts).forEach(function(cptKey) {
        var cptName = crmpn_cpts[cptKey];
        var searchToggleSelector = '.crmpn-' + cptKey + '-search-toggle';
        var searchInputSelector = '.crmpn-' + cptKey + '-search-input';
        var searchWrapperSelector = '.crmpn-' + cptKey + '-search-wrapper';
        var listSelector = '.crmpn-crmpn_' + cptKey + '-list';
        var listWrapperSelector = '.crmpn-crmpn_' + cptKey + '-list-wrapper';
        var addNewSelector = '.crmpn-' + cptKey + '[data-crmpn_' + cptKey + '-id="0"]';

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

            if (searchInput.hasClass('crmpn-display-none')) {
              // Show search input
              searchInput.removeClass('crmpn-display-none').focus();
              searchToggle.text('close');
              searchWrapper.addClass('crmpn-search-active');
            } else {
              // Hide search input and clear filter
              searchInput.addClass('crmpn-display-none').val('');
              searchToggle.text('search');
              searchWrapper.removeClass('crmpn-search-active');
              
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
                var itemTitle = $(this).find('.crmpn-display-inline-table a span').first().text().toLowerCase();
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

              searchInput.addClass('crmpn-display-none').val('');
              searchToggle.text('search');
              searchWrapper.removeClass('crmpn-search-active');
              
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
        Object.keys(crmpn_cpts).forEach(function(cptKey) {
          var searchWrapperSelector = '.crmpn-' + cptKey + '-search-wrapper';
          var searchInputSelector = '.crmpn-' + cptKey + '-search-input';
          var searchToggleSelector = '.crmpn-' + cptKey + '-search-toggle';
          var listSelector = '.crmpn-crmpn_' + cptKey + '-list';
          var listWrapperSelector = '.crmpn-crmpn_' + cptKey + '-list-wrapper';

          if ($(e.target).closest(searchWrapperSelector).length) {
            clickedInsideSearch = true;
          }

          // Find active search input
          var searchInput = $(searchInputSelector + ':not(.crmpn-display-none)');
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
          activeSearchInput.addClass('crmpn-display-none').val('');
          activeSearchToggle.text('search');
          activeSearchWrapper.removeClass('crmpn-search-active');
          
          // Show all items
          activeItemsList.find('li').show();
        }
      });
    }
  });

  $(document).on('click', '.crmpn-toggle', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    var crmpn_toggle = $(this);

    if (crmpn_toggle.find('i').length) {
      if (crmpn_toggle.siblings('.crmpn-toggle-content').is(':visible')) {
        crmpn_toggle.find('i').text('add');
      } else {
        crmpn_toggle.find('i').text('clear');
      }
    }

    crmpn_toggle.siblings('.crmpn-toggle-content').fadeToggle();
  });
})(jQuery);
