(function ($, debounce) {

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

  Drupal.behaviors.navbarFlexibleHeight = {
    attach: function (context, settings) {

      function headerFlexibleHeight() {
        var $navbarHeight = $('.navbar-fixed-top').height();
        var $mainContent = $('.main-container ');

        if (window.matchMedia('(min-width: 976px)').matches) {
          $mainContent.css({
            'padding-top': $navbarHeight,
            'min-height': `calc(100vh - ${$navbarHeight}px)`
          })
        } else {
          $mainContent.css({
            'padding-top': '0',
            'min-height': '100vh'
          })
        }
      }

      headerFlexibleHeight();

      var headerFlexibleHeightBehaviour = debounce(function () {
        headerFlexibleHeight();
      }, 250);
      window.addEventListener('resize', headerFlexibleHeightBehaviour);

    }
  }

})(jQuery, Drupal.debounce);
