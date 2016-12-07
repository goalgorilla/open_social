(function ($) {

  Drupal.behaviors.navbarCollapse = {
    attach: function (context, settings) {

      $(document).ready(function() {
        $('.dropdown-toggle').on('click', function() {
          $('.navbar-collapse').collapse('hide');
        });

      }); // End of $(document).ready

    }
  }

})(jQuery);
