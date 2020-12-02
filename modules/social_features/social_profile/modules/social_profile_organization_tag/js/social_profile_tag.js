(function ($, Drupal) {

  Drupal.behaviors.tooltip = {
    attach: function (context, settings) {

      let tag = $('.profile-organization-tag');

      tag.on('mouseenter', (event) => {
        $(event.currentTarget).addClass('open');
      });

      tag.on('mouseout', (event) => {
        $(event.currentTarget).removeClass('open');
      });
    }
  }

})(jQuery, Drupal);
