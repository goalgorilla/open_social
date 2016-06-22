
(function($){
  $(function(){

    var screen_sm_min = 767;
    var window_width = $(window).width();

    if (window_width > screen_sm_min ) {
      var tableOfContents = $('.table-of-contents'),
          indexBanner = $('#index-banner');

      if (tableOfContents.length) {
        $('.toc-wrapper').affix({
            offset: {
              top: tableOfContents.offset().top - 50
            }
          })
      }
      else if (indexBanner.length) {
        $('.toc-wrapper').affix({
          offset: {
            top: indexBanner.height()
          }
        })
      }
      else {
        $('.toc-wrapper').affix({
          offset: {
            top: 0
          }
        })
      }

    }

		$('[data-toggle="tabs"] a').click(function (e) {
			e.preventDefault();
		  $(this).tab('show');
    });

    // Plugin initialization
    //$('.scrollspy').scrollSpy();
    var scrollSpyItem = '#scrollspy';
    $('body').scrollspy({
      target: scrollSpyItem,
      offset: 200
    });

    $(scrollSpyItem).find('a').on('click', function(event){
      if (this.hash !== "") {
        event.preventDefault();

        var hash = this.hash;

        $('html, body').animate({
          scrollTop: $(hash).offset().top - 200
        }, 400, function () {
          window.location.hash = hash;
        });
      }
    });


  }); // end of document ready
})(jQuery); // end of jQuery name space
