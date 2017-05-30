/**
 * @file
 * Init navbar-search.
 */

(function ($) {

    'use strict';

    Drupal.behaviors.navbarSearch = {
        attach: function (context, settings) {
            new UISearch(document.getElementById('navbar-search'));

            $('#content').on('click', function(){
              $('.navbar-toggle:not(.collapsed)').click();
            });
        }
  };

})(jQuery);
