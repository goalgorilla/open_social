(function ($) {

  Drupal.behaviors.navbarAddContent = {
    attach: function (context, settings) {
      $('.navbar-nav .dropdown .dropdown-menu').each(function () {
        if($(this).children().length == 0){
          $(this).parent().addClass('hide');
        }
      });
    }
  }

})(jQuery);
