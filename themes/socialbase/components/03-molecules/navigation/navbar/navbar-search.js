(function ($) {

  /*
  ** Behaviour when user clicks the element
  * with class navbar__open-search-block the body gets
  * a class which opens the form. This file is part
  * of the navbar component.
   */
  Drupal.behaviors.initNavbarSearch = {
    attach: function (context, settings) {
      $('.navbar__open-search-block').on('click', function (e) {
        e.preventDefault();
        $('body').addClass('mode-search');
        $('.search-take-over .form-text').focus();
      });

      $('.btn--close-search-take-over').on('click', function () {
        $('body').removeClass('mode-search');
        $('.search-take-over .form-text').blur();
      });

      $('body').keydown(function(e) {
        if (e.keyCode == 27) {
          $('body').removeClass('mode-search');
          $('.search-take-over .form-text').blur();
        }
      });

    }

  };

})(jQuery);