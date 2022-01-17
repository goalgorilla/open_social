(function ($, Drupal) {

  Drupal.behaviors.tooltip = {
    attach: function (context, settings) {

      var tag = $('.profile-organization-tag');

      tag.each(function () {
        var el = $(this);
        var parentW = el.closest('.teaser__body').width();
        var position = el.position();
        var percentage = (position.left / (parentW * 0.01)).toString();
        var text = el.find('.text');
        var conditionPercentage = '50';

        // Reset `overflow` style for the `teaser__content-text` and `teaser__published-author` classes.
        var teaserC = el.closest('.teaser__content-text');
        var teaserP = el.closest('.teaser__published-author');

        teaserC.css('overflow', 'visible');
        teaserP.css('overflow', 'visible');

        if($('body.path-user').length === 0) {
          console.log(1);
          if (percentage >= conditionPercentage) {
            text.css({
              'left': 'auto',
              'right': '100%'
            });
          }
          else if (percentage <= conditionPercentage) {
            text.css({
              'left': '100%',
              'right': 'auto'
            });
          }
          else {
            text.css({
              'left': '50%',
              'right': 'auto',
              'margin-left': -(150 / 2) + 'px'
            });
          }
        }
      });

      tag.on('mouseenter', (event) => {
        $(event.currentTarget).addClass('open');
      });

      tag.on('mouseout', (event) => {
        $(event.currentTarget).removeClass('open');
      });
    }
  }

})(jQuery, Drupal);
