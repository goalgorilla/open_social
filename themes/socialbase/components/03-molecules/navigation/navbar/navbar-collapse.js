(function ($) {

  Drupal.behaviors.navbarCollapse = {
    attach: function (context, settings) {

      $('.dropdown-toggle, #content').on('click', function() {
        $('.navbar-collapse').collapse('hide');
      });

    }

  };

})(jQuery);
