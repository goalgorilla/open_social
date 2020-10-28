(function ($) {

  Drupal.behaviors.navbarCollapse = {
    attach: function (context, settings) {

      // Delegate the event to body to prevent screenreaders from thinking
      // teasers are clickable.
      $('body').on('click', '.dropdown-toggle, #content', function() {
        $('.navbar-collapse').collapse('hide');
      });

    }

  };

})(jQuery);
