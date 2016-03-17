jQuery(document).ready(function($){

  // open/close filter on mobile
	$('.js-open-canvas').on('click', function(){
		$('.off-canvas').addClass('is-open');
	});

  $('.js-close-canvas').on('click', function(){
		$('.off-canvas').removeClass('is-open');
	});

});
