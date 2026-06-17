(function ($, debounce, once) {

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

  // Keeps .main-container clear of the fixed navbar when its height changes
  // (e.g. wraps to two lines for anonymous users with extra header items).
  Drupal.behaviors.navbarFlexibleHeight = {
    attach: function (context) {
      // Avoid duplicate ResizeObservers when Drupal re-attaches behaviors (AJAX/BigPipe).
      once('navbar-flexible-height', '.navbar-fixed-top, .navbar-second-line', context).forEach(function (navbar) {
        var $mainContent = $('.main-container');
        // Measure .container--navbar only — not the absolute .navbar-collapse panel.
        var navbarContainer = navbar.querySelector('.container--navbar') || navbar;

        var updateHeight = function () {
          var navbarContainerRect = navbarContainer.getBoundingClientRect();
          var navbarHeight = navbarContainerRect.height;
          var isDesktop = window.matchMedia('(min-width: 976px)').matches;
          var isLoggedIn = document.body.classList.contains('user-logged-in');

          // Used by navbar.scss: height: calc(100vh - var(--navbar-offset)).
          navbar.style.setProperty('--navbar-offset', navbarContainerRect.bottom + 'px');

          // Offset content for logged-in users on desktop, or anonymous on all viewports.
          if ((isLoggedIn && isDesktop) || !isLoggedIn) {
            $mainContent.css({
              'padding-top': navbarHeight + 'px',
              'min-height': 'calc(100vh - ' + navbarHeight + 'px)'
            });
          } else {
            $mainContent.css({
              'padding-top': '0px',
              'min-height': '100vh'
            });
          }
        };

        var observer = new ResizeObserver(function () {
          updateHeight();
        });

        observer.observe(navbarContainer);
      });
    }
  };

})(jQuery, Drupal.debounce, once);
