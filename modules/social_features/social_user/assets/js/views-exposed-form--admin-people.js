(function ($, Drupal) {
  Drupal.behaviors.socialUser = {
    attach: function attach(context) {
      var $viewUserAdmin = $('.view-user-admin-people');

      if ($viewUserAdmin) {
        $viewUserAdmin.each(function () {
          var $showMore = $(this).find('.views-ef-fieldset-container-1 > summary');
          var textLess = Drupal.t('Show less');
          var textMore = Drupal.t('Show more');

          $showMore.on('click', function () {
            if ($showMore.attr('aria-expanded') === 'false') {
              $showMore.text(textLess);
            } else {
              $showMore.text(textMore);
            }
          });
        });
      }
    }
  };
})(jQuery, Drupal);
