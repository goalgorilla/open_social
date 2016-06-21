/**
 * @file
 * Handles AJAX fetching of views, including filter submission and response.
 */

(function ($) {

    'use strict';

    Drupal.behaviors.navbarSearch = {
        attach: function (context, settings) {
            new UISearch(document.getElementById('navbar-search'));
        }
  };

})(jQuery);
