/**
 * @file
 * Applies accessibility enhancements that are not easy to make through Twig.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.social_core_accessibility = {
    attach: function (context, _) {
      // Ensure links that are excluded from the accessibility tree can't be
      // focused with the keyboard because this is confusing.
      $('[aria-hidden="true"] a', context)
        .once('social_core_accessibility')
        .attr('tabindex', -1)
        .attr('aria-hidden', "true");
    }
  }
})(jQuery);
