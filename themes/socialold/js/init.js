
(function($){
  $(function(){

    var screen_sm_min = 767;
    var window_width = $(window).width();
    if (window_width > screen_sm_min ) {
      var tableOfContents = $('.table-of-contents');

      if (tableOfContents.length) {
        $('.toc-wrapper').affix({
            offset: {
              top: tableOfContents.offset().top - 74
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



    $('[data-toggle="tooltip"]').tooltip();

    $('[data-toggle="popover"]').popover({
      content: function () {
        timer = setTimeout(function() { popoverOpen = true; }, 100);
        return $(this.getAttribute('href')).html();
      },
      container: 'body'
    });

    $('body').on('click', function (e) {
      $('[data-toggle=popover]').each(function () {
        // hide any open popovers when the anywhere else in the body is clicked
        if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
          $(this).popover('hide');
        }
      });
    });

  }); // end of document ready
})(jQuery); // end of jQuery name space
