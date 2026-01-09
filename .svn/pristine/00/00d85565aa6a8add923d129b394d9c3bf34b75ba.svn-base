(function($) {
	'use strict';

  $(document).ready(function() {
    if($('.pn-customers-manager-tooltip').length) {
      $('.pn-customers-manager-tooltip').tooltipster({maxWidth: 300, delayTouch:[0, 4000], customClass: 'pn-customers-manager-tooltip'});
    }

    if ($('.pn-customers-manager-select').length) {
      $('.pn-customers-manager-select').each(function(index) {
        if ($(this).attr('multiple') == 'true') {
          // For a multiple select
          $(this).pn_customers_manager_Selector({
            multiple: true,
            searchable: true,
            placeholder: pn_customers_manager_i18n.select_options,
          });
        } else {
          // For a single select
          $(this).pn_customers_manager_Selector();
        }
      });
    }

    $.trumbowyg.svgPath = pn_customers_manager_trumbowyg.path;
    $('.pn-customers-manager-wysiwyg').each(function(index, element) {
      $(this).trumbowyg();
    });
  });
})(jQuery);
