(function($) {
	'use strict';

  $(document).ready(function() {
    if($('.customers-manager-pn-tooltip').length) {
      $('.customers-manager-pn-tooltip').tooltipster({maxWidth: 300, delayTouch:[0, 4000], customClass: 'customers-manager-pn-tooltip'});
    }

    if ($('.customers-manager-pn-select').length) {
      $('.customers-manager-pn-select').each(function(index) {
        if ($(this).attr('multiple') == 'true') {
          // For a multiple select
          $(this).CUSTOMERS_MANAGER_PN_Selector({
            multiple: true,
            searchable: true,
            placeholder: customers_manager_pn_i18n.select_options,
          });
        } else {
          // For a single select
          $(this).CUSTOMERS_MANAGER_PN_Selector();
        }
      });
    }

    $.trumbowyg.svgPath = customers_manager_pn_trumbowyg.path;
    $('.customers-manager-pn-wysiwyg').each(function(index, element) {
      $(this).trumbowyg();
    });
  });
})(jQuery);
