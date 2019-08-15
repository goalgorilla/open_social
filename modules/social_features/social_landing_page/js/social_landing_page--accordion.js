(function ($) {
  Drupal.behaviors.socialLandingPageAccordion = {
    attach: function (context, settings) {

      var $accordion = $('.paragraph--type--accordion', context);

      $accordion.each(function () {
        var $currentAccord = $(this);
        var $accordItem = $currentAccord.find('.paragraph--type--accordion-item');
        var $accordText = $accordItem.find('.card__text-accord');

        $accordItem.find('.card__title-accord').on('click', function () {
          var $currentTitle = $(this);

          $accordText.slideUp();
          if ($currentTitle.hasClass('is-active')) {
            $currentTitle.removeClass('is-active');
            $currentTitle.next().slideUp();
          }
          else {
            $accordItem.find('.card__title-accord').removeClass('is-active');
            $currentTitle.addClass('is-active');
            $currentTitle.next().slideDown();
          }
        });
      });

    }
  };
})(jQuery);
