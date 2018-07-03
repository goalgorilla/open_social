(function ($) {
  Drupal.behaviors.socialLandingPage = {
    attach: function (context, settings) {
      var section = $('.paragraph--section');
      var featured = 'paragraph--featured';

      $(section).each(function () {
        if ($(this).children(':first').hasClass(featured)) {
          if ($(this).next().children(":first").hasClass(featured) || $(this).prev().children(':first').hasClass(featured)) {
            $(this).children(':first').addClass('multiple');

            if (!$(this).prev().children(":first").hasClass(featured)) {
              $(this).children(':first').addClass('first');
            }

            if (!$(this).next().children(":first").hasClass(featured)) {
              $(this).children(':first').addClass('last');
            }
          }
        }
      });
    }
  };
})(jQuery);
