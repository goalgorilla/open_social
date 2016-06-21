
(function($){
  $(function(){

    var screen_sm_min = 767;
    var window_width = $(window).width();

    if (window_width > screen_sm_min ) {
      // Floating-Fixed table of contents
      if ($('.table-of-contents').length) {
        $('.toc-wrapper').pushpin({ top: $('.table-of-contents').offset().top, offset: 50 });
      }
      else if ($('#index-banner').length) {
        $('.toc-wrapper').pushpin({ top: $('#index-banner').height() });
      }
      else {
        $('.toc-wrapper').pushpin({ top: 0 });
      }

    }

    // Toggle Flow Text
    var toggleFlowTextButton = $('#flow-toggle');
    toggleFlowTextButton.click( function(){
      $('#flow-text-demo').children('p').each(function(){
          $(this).toggleClass('flow-text');
        });
    });

		$('[data-toggle="tabs"] a').click(function (e) {
			e.preventDefault();
		  $(this).tab('show');
    });



  }); // end of document ready
})(jQuery); // end of jQuery name space
