/**
 * @file
 * Applies accessibility enhancements that are not easy to make through Twig.
 */

(function ($, once) {
  'use strict';

  Drupal.behaviors.social_core_accessibility = {
    attach: function (context, _) {
      // Ensure links that are excluded from the accessibility tree can't be
      // focused with the keyboard because this is confusing.
      const $social_core_accessibility_once = $(once('social_core_accessibility', '[aria-hidden="true"] a', context));
      $social_core_accessibility_once.attr('tabindex', -1).attr('aria-hidden', "true");
    }
  }
})(jQuery, once);
