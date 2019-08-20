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
          $accordItem.find('.card__title-accord').find('svg use')
            .attr('xlink:href', '#icon-expand_more');

          if ($currentTitle.hasClass('is-active')) {
            $currentTitle.find('svg use')
              .attr('xlink:href', '#icon-expand_more');
            $currentTitle.removeClass('is-active');
            $currentTitle.next().slideUp();

          }
          else {
            $accordItem.find('.card__title-accord').removeClass('is-active');
            $currentTitle.addClass('is-active');
            $currentTitle.next().slideDown();
            $currentTitle.find('svg use')
              .attr('xlink:href', '#icon-expand-less');
          }
        });
      });

    }
  };
})(jQuery);
