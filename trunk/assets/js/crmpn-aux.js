(function($) {
	'use strict';

  $(document).ready(function() {
    if($('.crmpn-tooltip').length) {
      $('.crmpn-tooltip').tooltipster({maxWidth: 300, delayTouch:[0, 4000], customClass: 'crmpn-tooltip'});
    }

    if ($('.crmpn-select').length) {
      $('.crmpn-select').each(function(index) {
        if ($(this).attr('multiple') == 'true') {
          // For a multiple select
          $(this).CRMPN_Selector({
            multiple: true,
            searchable: true,
            placeholder: crmpn_i18n.select_options,
          });
        } else {
          // For a single select
          $(this).CRMPN_Selector();
        }
      });
    }

    $.trumbowyg.svgPath = crmpn_trumbowyg.path;
    $('.crmpn-wysiwyg').each(function(index, element) {
      $(this).trumbowyg();
    });
  });
})(jQuery);
