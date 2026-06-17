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

      $(once('navbarCollapseMainNav', '#main-navigation', context)).each(function () {
        var $mainNavigation = $(this);
        var $bodyEl = $('body');

        // The open panel is full viewport height (100vh - --navbar-offset), but
        // Bootstrap Collapse animates height to content scrollHeight. Those values
        // disagree, so the panel grew to the menu content height then jumped to the
        // full panel — a visible lag/hitch. We disable that height transition and
        // slide with translateY instead (panel height stays fixed). Drive the mobile menu slide in navbar.scss via body classes
        $mainNavigation.on('show.bs.collapse', function () {
          $bodyEl
            .addClass('open-navbar-menu')
            .removeClass('navbar-menu-closing navbar-menu-opening');

          requestAnimationFrame(function () {
            $bodyEl.addClass('navbar-menu-opening');
          });
        });

        $mainNavigation.on('shown.bs.collapse', function () {
          // Open transition finished; .in keeps translateY(0) on its own.
          $bodyEl.removeClass('navbar-menu-opening');
        });

        $mainNavigation.on('hide.bs.collapse', function () {
          // Start close: transition from translateY(0) back to -100%.
          $bodyEl
            .addClass('navbar-menu-closing')
            .removeClass('navbar-menu-opening');
        });

        $mainNavigation.on('hidden.bs.collapse', function () {
          // Fully closed: unlock scroll and clear direction class.
          $bodyEl.removeClass('open-navbar-menu navbar-menu-closing');
        });
      });

    }

  };

})(Drupal, jQuery, once);
