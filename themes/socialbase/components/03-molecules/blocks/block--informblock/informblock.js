/**
 * @file
 * Inform block click behaviour.
 */
(function ($, Drupal) {

  window.informBlockClick = (function() {
    $('.block-data-policy').on('click', function() {

      var viewportWidth = window.innerWidth;
      var tabletLandscapeUpBreakpoint = 900;

      if (viewportWidth < tabletLandscapeUpBreakpoint) {
        $(this).find('footer a').click();
      }
    });
  })();

})(jQuery, Drupal);
