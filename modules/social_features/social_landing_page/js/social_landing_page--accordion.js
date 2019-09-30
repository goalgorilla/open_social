(function ($) {
  Drupal.behaviors.socialLandingPageAccordion = {
    attach: function (context, settings) {

      var $accordion = $('.paragraph--type--accordion', context);

      $accordion.each(function () {
        var $currentAccord = $(this);
        var $accordItem = $currentAccord.find('.paragraph--type--accordion-item');
        var $accordTitle = $accordItem.find('.card__title-accord');
        var accordSvg = $accordTitle.find('svg use');
        var $accordText = $accordItem.find('.card__text-accord');

        $accordItem.find('.card__title-accord').on('click', function () {
          var $currentTitle = $(this);
          var $currentText = $currentTitle.next();
          var $svg = $currentTitle.find('svg use');

          // Default behavior accordion items.
          $accordText.slideUp();
          accordSvg.attr('xlink:href', '#icon-expand_more');

          //Conditions open/close accordion items.
          if ($currentTitle.hasClass('is-active')) {

            // Close accordion item(s).
            $svg.attr('xlink:href', '#icon-expand_more');
            $currentTitle.removeClass('is-active');
            $currentText.slideUp();

          }
          else {
            // Open accordion item.
            $accordTitle.removeClass('is-active');
            $currentTitle.addClass('is-active');
            $currentText.slideDown();
            $svg.attr('xlink:href', '#icon-expand-less');
          }
        });
      });

    }
  };
})(jQuery);
