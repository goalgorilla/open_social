(function ($, Drupal) {

  Drupal.behaviors.socialActivitySendEmail = {
    attach: function attach(context) {
      var $context = $(context);

      $context.find('.form-collapse .card__title > a').each(function () {
        $(this).on('click', function () {
          var $this = $(this);
          var $icon = $this.find('.icon');
          var $iconUse = $icon.find('use');

          setTimeout(function () {
            if ($this.hasClass('collapsed')) {
              $icon.removeClass('icon-expand_less')
                .addClass('icon-expand_more');
              $iconUse.attr('href', '#icon-expand_more');
            }
            else {
              $icon.removeClass('icon-expand_more')
                .addClass('icon-expand_less');
              $iconUse.attr('href', '#icon-expand-less');
            }
          }, 150);
        });
      });
    }
  };

})(jQuery, Drupal);
