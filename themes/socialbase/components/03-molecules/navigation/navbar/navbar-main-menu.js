(function ($) {

  Drupal.behaviors.navbarMainMenu = {
    attach: function (context, settings) {
      $('.menu-main > .main > .expanded > .dropdown-menu > .expanded > a').removeAttr('data-toggle')
        .removeClass('dropdown-toggle');
    }

  };

})(jQuery);
