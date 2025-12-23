(function($) {
	'use strict';

	function customers_manager_pn_timer(step) {
		var step_timer = $('.customers-manager-pn-player-step[data-customers-manager-pn-step="' + step + '"] .customers-manager-pn-player-timer');
		var step_icon = $('.customers-manager-pn-player-step[data-customers-manager-pn-step="' + step + '"] .customers-manager-pn-player-timer-icon');
		
		if (!step_timer.hasClass('timing')) {
			step_timer.addClass('timing');

      setInterval(function() {
      	step_icon.fadeOut('fast').fadeIn('slow').fadeOut('fast').fadeIn('slow');
      }, 5000);

      setInterval(function() {
      	step_timer.text(Math.max(0, parseInt(step_timer.text()) - 1)).fadeOut('fast').fadeIn('slow').fadeOut('fast').fadeIn('slow');
      }, 60000);
		}
	}

	$(document).on('click', '.customers-manager-pn-popup-player-btn', function(e){
  	customers_manager_pn_timer(1);
	});

	$('.customers-manager-pn-carousel-main-images .owl-carousel').owlCarousel({
    margin: 10,
    center: true,
    nav: false, 
    autoplay: true, 
    autoplayTimeout: 5000, 
    autoplaySpeed: 2000, 
    pagination: true, 
    responsive:{
      0:{
        items: 2,
      },
      600:{
        items: 3,
      },
      1000:{
        items: 4,
      }
    }, 
  });
})(jQuery);
