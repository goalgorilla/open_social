(function ($, Drupal, debounce) {

  Drupal.behaviors.tooltip = {
    attach: function (context, settings) {

      var tag = $('.profile-organization-tag');

      tag.each(function () {
        var el = $(this);
        var teaser = el.closest('.teaser');
        var parentW = el.closest('.teaser__body').width();
        var position = el.position();
        var percentage = (position.left / (parentW * 0.01)).toString();
        var text = el.find('.text');
        var conditionPercentage = '50';

        // Add extra class to the teaser with profile organization tag.
        teaser.addClass('js-teaser-profile-org-tag');

        // Reset `overflow` style for the `teaser__content-text` and `teaser__published-author` classes.
        var teaserC = el.closest('.teaser__content-text');
        var teaserP = el.closest('.teaser__published-author');
        var resetStyles = {'overflow': 'visible', 'white-space': 'normal'};
        var revertStyles = {'overflow': 'hidden', 'white-space': 'nowrap'};

        function viewportProfileOrgChanges() {
          if(teaser.hasClass('js-teaser-profile-org-tag')) {
            if ($(window).width() >= 1025) {
              teaserC.css(resetStyles);
              teaserP.css(resetStyles);
            }
            else{
              teaserC.css(revertStyles);
              teaserP.css(revertStyles);
            }
          }
        }

        viewportProfileOrgChanges();

        // Debounce.
        var viewportChange = debounce(function() {
          viewportProfileOrgChanges()
        }, 250);

        window.addEventListener('resize', viewportChange);

        if($('body.path-user').length === 0) {
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

})(jQuery, Drupal, Drupal.debounce);
