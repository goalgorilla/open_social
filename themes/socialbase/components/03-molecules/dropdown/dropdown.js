(function ($) {

  Drupal.behaviors.dropdownJS = {
    attach: function (context, settings) {

      // If JS is enabled.
      $('body').addClass('js');

    }

  };

})(jQuery);
