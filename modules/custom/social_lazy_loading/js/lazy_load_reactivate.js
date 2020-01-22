(function ($) {

  'use strict';

  Drupal.behaviors.socialLazyLoadReactivate = {
    attach: function (context, setting) {
      $('.paragraph--type--accordion .paragraph--type--accordion-item .card__title-accord', context).on('click', function () {
        var bLazy = new Blazy();
        bLazy.revalidate();
      });
    }
  };

})(jQuery);
