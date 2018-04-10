/**
 * @file
 * Inform block click behaviour.
 */
(function ($, Drupal) {

  window.informBlockClick = (function() {
    $('.block-gdpr-consent').on('click', function() {

      var viewportWidth = window.innerWidth;
      var tabletLandscapeUpBreakpoint = 900;

      if (viewportWidth < tabletLandscapeUpBreakpoint) {
        $(this).find('footer a').click();
      }
    });
  })();

})(jQuery, Drupal);
