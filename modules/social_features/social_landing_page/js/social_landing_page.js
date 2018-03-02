(function ($) {
  Drupal.behaviors.socialLandingPage = {
    attach: function (context, settings) {
      var section = $('.paragraph--featured');
      $(section).each(function (index, value) {
        $(this).addClass('multiple featured_0' + index);
        $(section).eq(0).addClass('first');
        $(section).eq(-1).addClass('last');
      });
    }
  };
})(jQuery);
