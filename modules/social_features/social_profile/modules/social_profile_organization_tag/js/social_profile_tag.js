(function ($, Drupal) {

  Drupal.behaviors.tooltip = {
    attach: function (context, settings) {

      $('.profile-organization-tag').hover(function () {
        $(this).toggleClass('open');
      });
    }
  }

})(jQuery, Drupal);
