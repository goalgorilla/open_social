(function ($) {

  Drupal.behaviors.navbarProfileDropdown = {
    attach: function (context, settings) {

      // Toggles inline display of profile dropdown menu items.
      var navbarResizeUpdate = function () {
        var viewportWidth = window.innerWidth;
        var tabletLandscapeUpBreakpoint = 900;

        if (viewportWidth >= tabletLandscapeUpBreakpoint) {
          $('.dropdown-menu', '.dropdown.profile.not-logged-in').removeClass().addClass('menu nav navbar-nav');
        }
        else {
          $('.menu.nav.navbar-nav', '.dropdown.profile.not-logged-in').removeClass().addClass('dropdown-menu');
        }

      };

      // Executed on document load and window resize.
      navbarResizeUpdate();
      $(window).resize(_.debounce(function(){
        navbarResizeUpdate()
      }, 500));

    }
  }

})(jQuery);
