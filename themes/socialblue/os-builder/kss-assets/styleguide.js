
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

    // Plugin initialization
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


    $('.tables-start').nextUntil('.tables-end', 'table').addClass('table');

    $(document.links).filter(function() {
      return this.hostname != window.location.hostname;
    }).attr('target', '_blank');

  }); // end of document ready
})(jQuery); // end of jQuery name space
