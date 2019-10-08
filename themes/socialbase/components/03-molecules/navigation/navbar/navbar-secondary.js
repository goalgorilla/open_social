(function ($) {

  Drupal.behaviors.navbarSecondaryAnchor = {
    attach: function (context, settings) {
      
      // General variables.
      var windowMain = $(window);
      var windowHeight = windowMain.height();
      var main = $('body, html');
      var mainHeight = main.height();

      // Secondaty navigation variables.
      var navSecondary = $('.navbar-secondary .navbar-nav');
      var activeItem  = navSecondary.find('li.active');
      var scrollTo = navSecondary.offset().top;

      windowMain.on('load', function () {
        if (windowMain.width() < 900) {
          if (activeItem.index() !== 0 && activeItem.index() !== -1 && (mainHeight >= (windowHeight * 2))) {
            main.animate({scrollTop: scrollTo +'px'}, 800);
          }
        }
      });

    }

  };

})(jQuery);
