(function ($) {

  'use strict';

  Drupal.behaviors.socialSlickSelect = {
    attach: function (context, setting) {
      $(".slick-select").once("socialInitializeSlickSelect").each(function (i, e) {
        $(this).ddslick();
      });
    }
  };

})(jQuery);
