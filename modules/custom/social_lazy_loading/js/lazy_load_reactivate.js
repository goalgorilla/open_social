(function ($) {

  'use strict';

  Drupal.behaviors.socialLazyLoadReactivate = {
    attach: function (context, setting) {
      var $accordion = $('.paragraph--type--accordion', context);

      $accordion.each(function () {
        var $currentAccord = $(this);
        var $accordItem = $currentAccord.find('.paragraph--type--accordion-item');
        $accordItem.find('.card__title-accord').on('click', function () {
            var bLazy = new Blazy();
            bLazy.revalidate();
        });
      });
    }
  };

})(jQuery);
