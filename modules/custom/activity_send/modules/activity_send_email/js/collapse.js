(function ($, Drupal) {

  Drupal.behaviors.socialActivitySendEmail = {
    attach: function attach(context) {
      var $context = $(context);

      $context.find('.form-email-notification .card__title > a').each(function () {
        $(this).on('click', function () {
          var $icon = $(this).find(' svg');

          if ($icon.hasClass('icon-expand_more')) {
            $icon.html('<use xlink:href="#icon-expand-less" />');
          }
          else {
            $icon.html('<use xlink:href="#icon-expand_more" />');
          }

          $icon.toggleClass('icon-expand_more', 'icon-expand-less');
          // return false;
        });
      });
    }
  };

})(jQuery, Drupal);
