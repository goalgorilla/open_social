(function ($, Drupal) {
  Drupal.behaviors.groupCoreComments = {
    attach: function (context) {

      $(window).on('load', function () {
        var $followTax = $('.social_follow_tax');
        var $groupAction = $followTax.find('.group-action');
        var $popupGeneral = $groupAction.find('.popup-info');

        $groupAction.each(function () {
          var $this = $(this);
          var $badge = $this.find('.badge ');
          var $popup = $this.find('.popup-info');
          var $popupH = $popup.outerHeight();

          $popup.css('top', (-$popupH - 5));

          $(window).on('resize', function () {
            var $popupHResize = $popup.outerHeight();
            $popup.css('top', (-$popupHResize - 5));
          });

          $badge.on('click', function (event) {
            event.preventDefault();
            $popupGeneral.removeClass('open');
            $(this).next().toggleClass('open');
          });

          $(document).click(function (event) {
            if ($(event.target).closest('.social_follow_tax .group-action .badge').length) {
              return;
            }
            if ($(event.target).closest('.social_follow_tax .group-action .popup-info').length) {
              return;
            }
            $badge.next().removeClass('open');
            event.stopPropagation();
          });
        });
      });

    }
  };
})(jQuery, Drupal);