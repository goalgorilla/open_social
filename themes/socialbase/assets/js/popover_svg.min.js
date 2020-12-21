/**
 * @file
 * Add SVG as sanitized option to popover.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.social_popover = {
    attach: function (context, settings) {

      if (!$.fn.popover) throw new Error('Popover requires tooltip.js')

      $('body').once('social_popover').each(function () {
        var myDefaultWhiteList = [];
        // Allow table elements
        myDefaultWhiteList.table = [];
        myDefaultWhiteList.td = [];
        myDefaultWhiteList.th = [];

        // Allow SVG's and use options
        myDefaultWhiteList.svg = ['viewBox'];
        myDefaultWhiteList.use = ['xlink:href', 'href', 'xlink'];

        // Extend the popover defaults so we can add the above to the popover.
        $.extend($.fn.popover.Constructor.DEFAULTS.whiteList, myDefaultWhiteList);
      });
    }
  };

})(jQuery, Drupal);
