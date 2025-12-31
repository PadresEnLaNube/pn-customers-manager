(function($) {
  'use strict';

  $(document).ready(function() {
    if ($('.pn-customers-manager-password-checker').length) {
      var pass_view_state = false;

      function pn_customers_manager_pass_check_strength(pass) {
        var strength = 0;
        var password = $('.pn-customers-manager-password-strength');
        var low_upper_case = password.closest('.pn-customers-manager-password-checker').find('.low-upper-case i');
        var number = password.closest('.pn-customers-manager-password-checker').find('.one-number i');
        var special_char = password.closest('.pn-customers-manager-password-checker').find('.one-special-char i');
        var eight_chars = password.closest('.pn-customers-manager-password-checker').find('.eight-character i');

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
          $('.pn-customers-manager-password-strength-bar').removeClass('pn-customers-manager-progress-bar-warning pn-customers-manager-progress-bar-success').addClass('pn-customers-manager-progress-bar-danger').css('width', '10%');
        } else if (strength == 3) {
          $('.pn-customers-manager-password-strength-bar').removeClass('pn-customers-manager-progress-bar-success pn-customers-manager-progress-bar-danger').addClass('pn-customers-manager-progress-bar-warning').css('width', '60%');
        } else if (strength == 4) {
          $('.pn-customers-manager-password-strength-bar').removeClass('pn-customers-manager-progress-bar-warning pn-customers-manager-progress-bar-danger').addClass('pn-customers-manager-progress-bar-success').css('width', '100%');
        }
      }

      $(document).on('click', '.pn-customers-manager-show-pass', function(e){
        e.preventDefault();
        var pn_customers_manager_btn = $(this);
        var password_input = pn_customers_manager_btn.siblings('.pn-customers-manager-password-strength');

        if (pass_view_state) {
          password_input.attr('type', 'password');
          pn_customers_manager_btn.find('i').text('visibility');
          pass_view_state = false;
        } else {
          password_input.attr('type', 'text');
          pn_customers_manager_btn.find('i').text('visibility_off');
          pass_view_state = true;
        }
      });

      $(document).on('keyup', ('.pn-customers-manager-password-strength'), function(e){
        pn_customers_manager_pass_check_strength($('.pn-customers-manager-password-strength').val());

        if (!$('#pn-customers-manager-popover-pass').is(':visible')) {
          $('#pn-customers-manager-popover-pass').fadeIn('slow');
        }

        if (!$('.pn-customers-manager-show-pass').is(':visible')) {
          $('.pn-customers-manager-show-pass').fadeIn('slow');
        }
      });
    }
    
    $(document).on('mouseover', '.pn-customers-manager-input-star', function(e){
      if (!$(this).closest('.pn-customers-manager-input-stars').hasClass('clicked')) {
        $(this).text('star');
        $(this).prevAll('.pn-customers-manager-input-star').text('star');
      }
    });

    $(document).on('mouseout', '.pn-customers-manager-input-stars', function(e){
      if (!$(this).hasClass('clicked')) {
        $(this).find('.pn-customers-manager-input-star').text('star_outlined');
      }
    });

    $(document).on('click', '.pn-customers-manager-input-star', function(e){
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      $(this).closest('.pn-customers-manager-input-stars').addClass('clicked');
      $(this).closest('.pn-customers-manager-input-stars').find('.pn-customers-manager-input-star').text('star_outlined');
      $(this).text('star');
      $(this).prevAll('.pn-customers-manager-input-star').text('star');
      $(this).closest('.pn-customers-manager-input-stars').siblings('.pn-customers-manager-input-hidden-stars').val($(this).prevAll('.pn-customers-manager-input-star').length + 1);
    });

    $(document).on('change', '.pn-customers-manager-input-hidden-stars', function(e){
      $(this).siblings('.pn-customers-manager-input-stars').find('.pn-customers-manager-input-star').text('star_outlined');
      $(this).siblings('.pn-customers-manager-input-stars').find('.pn-customers-manager-input-star').slice(0, $(this).val()).text('star');
    });

    if ($('.pn-customers-manager-field[data-pn-customers-manager-parent]').length) {
      cm_pn_form_update();

      $(document).on('change', '.pn-customers-manager-field[data-pn-customers-manager-parent~="this"]', function(e) {
        cm_pn_form_update();
      });
    }

    if ($('.pn-customers-manager-html-multi-group').length) {
      $(document).on('click', '.pn-customers-manager-html-multi-remove-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var pn_customers_manager_users_btn = $(this);

        if (pn_customers_manager_users_btn.closest('.pn-customers-manager-html-multi-wrapper').find('.pn-customers-manager-html-multi-group').length > 1) {
          $(this).closest('.pn-customers-manager-html-multi-group').remove();
        } else {
          $(this).closest('.pn-customers-manager-html-multi-group').find('input, select, textarea').val('');
        }
      });

      $(document).on('click', '.pn-customers-manager-html-multi-add-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        $(this).closest('.pn-customers-manager-html-multi-wrapper').find('.pn-customers-manager-html-multi-group:first').clone().insertAfter($(this).closest('.pn-customers-manager-html-multi-wrapper').find('.pn-customers-manager-html-multi-group:last'));
        $(this).closest('.pn-customers-manager-html-multi-wrapper').find('.pn-customers-manager-html-multi-group:last').find('input, select, textarea').val('');

        $(this).closest('.pn-customers-manager-html-multi-wrapper').find('.pn-customers-manager-input-range').each(function(index, element) {
          $(this).siblings('.pn-customers-manager-input-range-output').html($(this).val());
        });
      });

      $('.pn-customers-manager-html-multi-wrapper').sortable({handle: '.pn-customers-manager-multi-sorting'});

      $(document).on('sortstop', '.pn-customers-manager-html-multi-wrapper', function(event, ui){
        pn_customers_manager_get_main_message(pn_customers_manager_i18n.ordered_element);
      });
    }

    if ($('.pn-customers-manager-input-range').length) {
      $('.pn-customers-manager-input-range').each(function(index, element) {
        $(this).siblings('.pn-customers-manager-input-range-output').html($(this).val());
      });

      $(document).on('input', '.pn-customers-manager-input-range', function(e) {
        $(this).siblings('.pn-customers-manager-input-range-output').html($(this).val());
      });
    }

    if ($('.pn-customers-manager-image-btn').length) {
      var image_frame;

      $(document).on('click', '.pn-customers-manager-image-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (image_frame){
          image_frame.open();
          return;
        }

        var pn_customers_manager_input_btn = $(this);
        var pn_customers_manager_images_block = pn_customers_manager_input_btn.closest('.pn-customers-manager-images-block').find('.pn-customers-manager-images');
        var pn_customers_manager_images_input = pn_customers_manager_input_btn.closest('.pn-customers-manager-images-block').find('.pn-customers-manager-image-input');

        var image_frame = wp.media({
          title: (pn_customers_manager_images_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_images : pn_customers_manager_i18n.select_image,
          library: {
            type: 'image'
          },
          multiple: (pn_customers_manager_images_block.attr('data-pn-customers-manager-multiple') == 'true') ? 'true' : 'false',
        });

        image_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (pn_customers_manager_images_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.edit_images : pn_customers_manager_i18n.edit_image,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(image_frame.options.library),
            multiple: (pn_customers_manager_images_block.attr('data-pn-customers-manager-multiple') == 'true') ? 'true' : 'false',
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
          pn_customers_manager_images_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            pn_customers_manager_images_block.append('<img src="' + $(this)[0].url + '" class="">');
          });

          pn_customers_manager_input_btn.text((pn_customers_manager_images_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_images : pn_customers_manager_i18n.select_image);
          pn_customers_manager_images_input.val(ids);
        });
      });
    }

    if ($('.pn-customers-manager-audio-btn').length) {
      var audio_frame;

      $(document).on('click', '.pn-customers-manager-audio-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (audio_frame){
          audio_frame.open();
          return;
        }

        var pn_customers_manager_input_btn = $(this);
        var pn_customers_manager_audios_block = pn_customers_manager_input_btn.closest('.pn-customers-manager-audios-block').find('.pn-customers-manager-audios');
        var pn_customers_manager_audios_input = pn_customers_manager_input_btn.closest('.pn-customers-manager-audios-block').find('.pn-customers-manager-audio-input');

        var audio_frame = wp.media({
          title: (pn_customers_manager_audios_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_audios : pn_customers_manager_i18n.select_audio,
          library : {
            type : 'audio'
          },
          multiple: (pn_customers_manager_audios_block.attr('data-pn-customers-manager-multiple') == 'true') ? 'true' : 'false',
        });

        audio_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (pn_customers_manager_audios_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_audios : pn_customers_manager_i18n.select_audio,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(audio_frame.options.library),
            multiple: (pn_customers_manager_audios_block.attr('data-pn-customers-manager-multiple') == 'true') ? 'true' : 'false',
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
          pn_customers_manager_audios_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            pn_customers_manager_audios_block.append('<div class="pn-customers-manager-audio pn-customers-manager-tooltip" title="' + $(this)[0].title + '"><i class="dashicons dashicons-media-audio"></i></div>');
          });

          $('.pn-customers-manager-tooltip').tooltipster({maxWidth: 300,delayTouch:[0, 4000], customClass: 'pn-customers-manager-tooltip'});
          pn_customers_manager_input_btn.text((pn_customers_manager_audios_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_audios : pn_customers_manager_i18n.select_audio);
          pn_customers_manager_audios_input.val(ids);
        });
      });
    }

    if ($('.pn-customers-manager-video-btn').length) {
      var video_frame;

      $(document).on('click', '.pn-customers-manager-video-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (video_frame){
          video_frame.open();
          return;
        }

        var pn_customers_manager_input_btn = $(this);
        var pn_customers_manager_videos_block = pn_customers_manager_input_btn.closest('.pn-customers-manager-videos-block').find('.pn-customers-manager-videos');
        var pn_customers_manager_videos_input = pn_customers_manager_input_btn.closest('.pn-customers-manager-videos-block').find('.pn-customers-manager-video-input');

        var video_frame = wp.media({
          title: (pn_customers_manager_videos_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_videos : pn_customers_manager_i18n.select_video,
          library : {
            type : 'video'
          },
          multiple: (pn_customers_manager_videos_block.attr('data-pn-customers-manager-multiple') == 'true') ? 'true' : 'false',
        });

        video_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (pn_customers_manager_videos_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_videos : pn_customers_manager_i18n.select_video,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(video_frame.options.library),
            multiple: (pn_customers_manager_videos_block.attr('data-pn-customers-manager-multiple') == 'true') ? 'true' : 'false',
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
          pn_customers_manager_videos_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            pn_customers_manager_videos_block.append('<div class="pn-customers-manager-video pn-customers-manager-tooltip" title="' + $(this)[0].title + '"><i class="dashicons dashicons-media-video"></i></div>');
          });

          $('.pn-customers-manager-tooltip').tooltipster({maxWidth: 300,delayTouch:[0, 4000], customClass: 'pn-customers-manager-tooltip'});
          pn_customers_manager_input_btn.text((pn_customers_manager_videos_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_videos : pn_customers_manager_i18n.select_video);
          pn_customers_manager_videos_input.val(ids);
        });
      });
    }

    if ($('.pn-customers-manager-file-btn').length) {
      var file_frame;

      $(document).on('click', '.pn-customers-manager-file-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (file_frame){
          file_frame.open();
          return;
        }

        var pn_customers_manager_input_btn = $(this);
        var pn_customers_manager_files_block = pn_customers_manager_input_btn.closest('.pn-customers-manager-files-block').find('.pn-customers-manager-files');
        var pn_customers_manager_files_input = pn_customers_manager_input_btn.closest('.pn-customers-manager-files-block').find('.pn-customers-manager-file-input');

        var file_frame = wp.media({
          title: (pn_customers_manager_files_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_files : pn_customers_manager_i18n.select_file,
          multiple: (pn_customers_manager_files_block.attr('data-pn-customers-manager-multiple') == 'true') ? 'true' : 'false',
        });

        file_frame.states.add([
          new wp.media.controller.Library({
            id: 'post-gallery',
            title: (pn_customers_manager_files_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.select_files : pn_customers_manager_i18n.select_file,
            priority: 20,
            toolbar: 'main-gallery',
            filterable: 'uploaded',
            library: wp.media.query(file_frame.options.library),
            multiple: (pn_customers_manager_files_block.attr('data-pn-customers-manager-multiple') == 'true') ? 'true' : 'false',
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
          pn_customers_manager_files_block.html('');

          $(attachments_arr).each(function(e){
            var sep = (e != (attachments_arr.length - 1))  ? ',' : '';
            ids += $(this)[0].id + sep;
            pn_customers_manager_files_block.append('<embed src="' + $(this)[0].url + '" type="application/pdf" class="pn-customers-manager-embed-file"/>');
          });

          pn_customers_manager_input_btn.text((pn_customers_manager_files_block.attr('data-pn-customers-manager-multiple') == 'true') ? pn_customers_manager_i18n.edit_files : pn_customers_manager_i18n.edit_file);
          pn_customers_manager_files_input.val(ids);
        });
      });
    }

    // CPT SEARCH FUNCTIONALITY
    // Use a single delegated event handler for all search toggles
    $(document).on('click', '.pn-customers-manager-cpt-search-toggle', function(e) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      var searchToggle = $(this);
      var searchInput = searchToggle.siblings('.pn-customers-manager-cpt-search-input');
      var searchWrapper = searchToggle.closest('.pn-customers-manager-cpt-search-wrapper');
      var list = searchToggle.closest('.pn-customers-manager-cpt-list');
      
      // Find the list wrapper - it could be cm_pn_org-list-wrapper or cm_pn_funnel-list-wrapper
      var listWrapper = list.siblings('.pn-customers-manager-cpt-list-wrapper');
      if (!listWrapper.length) {
        // Try finding it within the list container
        listWrapper = list.find('.pn-customers-manager-cpt-list-wrapper');
      }
      var itemsList = listWrapper.find('ul');

      if (searchInput.length && searchInput.hasClass('pn-customers-manager-display-none')) {
        // Show search input
        searchInput.removeClass('pn-customers-manager-display-none').focus();
        searchToggle.text('close');
        if (searchWrapper.length) {
          searchWrapper.addClass('pn-customers-manager-search-active');
        }
      } else if (searchInput.length) {
        // Hide search input and clear filter
        searchInput.addClass('pn-customers-manager-display-none').val('');
        searchToggle.text('search');
        if (searchWrapper.length) {
          searchWrapper.removeClass('pn-customers-manager-search-active');
        }
        
        // Show all items
        if (itemsList.length) {
          itemsList.find('li').show();
        }
      }
    });

    // Filter items on keyup - use delegated event handler
    $(document).on('keyup', '.pn-customers-manager-cpt-search-input', function(e) {
      var searchInput = $(this);
      var searchTerm = searchInput.val().toLowerCase().trim();
      var list = searchInput.closest('.pn-customers-manager-cpt-list');
      var listWrapper = list.siblings('.pn-customers-manager-cpt-list-wrapper');
      if (!listWrapper.length) {
        listWrapper = list.find('.pn-customers-manager-cpt-list-wrapper');
      }
      var itemsList = listWrapper.find('ul');
      
      // Determine the CPT type from the list classes
      var cptKey = '';
      if (list.hasClass('pn-customers-manager-cm_pn_org-list')) {
        cptKey = 'cm_pn_org';
      } else if (list.hasClass('pn-customers-manager-cm_pn_funnel-list')) {
        cptKey = 'cm_pn_funnel';
      }
      
      var addNewSelector = '.pn-customers-manager-add-new-cpt[data-' + cptKey + '-id="0"]';
      var items = itemsList.find('li:not(' + addNewSelector + ')');

      if (searchTerm === '') {
        // Show all items when search is empty
        items.show();
        // Also show the "Add new" item
        itemsList.find(addNewSelector).show();
      } else {
        // Filter items based on title
        items.each(function() {
          var itemTitle = $(this).find('.pn-customers-manager-display-inline-table a span').first().text().toLowerCase();
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
    $(document).on('keydown', '.pn-customers-manager-cpt-search-input', function(e) {
      if (e.keyCode === 27) { // Escape key
        var searchInput = $(this);
        var searchToggle = searchInput.siblings('.pn-customers-manager-cpt-search-toggle');
        var searchWrapper = searchInput.closest('.pn-customers-manager-cpt-search-wrapper');
        var list = searchInput.closest('.pn-customers-manager-cpt-list');
        var listWrapper = list.siblings('.pn-customers-manager-cpt-list-wrapper');
        if (!listWrapper.length) {
          listWrapper = list.find('.pn-customers-manager-cpt-list-wrapper');
        }
        var itemsList = listWrapper.find('ul');

        searchInput.addClass('pn-customers-manager-display-none').val('');
        searchToggle.text('search');
        searchWrapper.removeClass('pn-customers-manager-search-active');
        
        // Show all items
        itemsList.find('li').show();
      }
    });

    // Single unified click outside handler for all search wrappers
    $(document).on('click', function(e) {
      // Check if clicked inside any search wrapper
      if ($(e.target).closest('.pn-customers-manager-cpt-search-wrapper').length) {
        return; // Don't close if clicked inside search wrapper
      }

      // Find active search input (not hidden)
      var activeSearchInput = $('.pn-customers-manager-cpt-search-input:not(.pn-customers-manager-display-none)');
      if (activeSearchInput.length) {
        var activeSearchToggle = activeSearchInput.siblings('.pn-customers-manager-cpt-search-toggle');
        var activeSearchWrapper = activeSearchInput.closest('.pn-customers-manager-cpt-search-wrapper');
        var activeList = activeSearchInput.closest('.pn-customers-manager-cpt-list');
        var activeListWrapper = activeList.siblings('.pn-customers-manager-cpt-list-wrapper');
        if (!activeListWrapper.length) {
          activeListWrapper = activeList.find('.pn-customers-manager-cpt-list-wrapper');
        }
        var activeItemsList = activeListWrapper.find('ul');

        activeSearchInput.addClass('pn-customers-manager-display-none').val('');
        activeSearchToggle.text('search');
        activeSearchWrapper.removeClass('pn-customers-manager-search-active');
        
        // Show all items
        activeItemsList.find('li').show();
      }
    });
  });

  $(document).on('click', '.pn-customers-manager-toggle', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    var pn_customers_manager_toggle = $(this);

    if (pn_customers_manager_toggle.find('i').length) {
      if (pn_customers_manager_toggle.siblings('.pn-customers-manager-toggle-content').is(':visible')) {
        pn_customers_manager_toggle.find('i').text('add');
      } else {
        pn_customers_manager_toggle.find('i').text('clear');
      }
    }

    pn_customers_manager_toggle.siblings('.pn-customers-manager-toggle-content').fadeToggle();
  });
})(jQuery);
