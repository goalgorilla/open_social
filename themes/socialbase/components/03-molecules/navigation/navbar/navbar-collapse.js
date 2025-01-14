(function (Drupal, $, once) {

  Drupal.behaviors.navbarCollapse = {
    attach: function (context, settings) {

      // Delegate the event to body to prevent screenreaders from thinking
      // teasers are clickable.

      var $body = $(once('navbarCollapse', 'body'));

      $body.on('click', '.dropdown-toggle, #content', function() {
        $('.navbar-collapse').collapse('hide');

        var headerDropDown = $('.navbar-default .dropdown');

        setTimeout(function () {
          if(headerDropDown.hasClass('open')) {
            $body.addClass('open-dropdown-menu');
          } else {
            $body.removeClass('open-dropdown-menu');
          }
        }, 0);
      });

    }

  };

})(Drupal, jQuery, once);
