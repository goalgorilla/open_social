/**
 * @file
 * Handles replacing the visible value of the picked visibility setting
 */

 (function ($) {

  'use strict';

  Drupal.behaviors.visibilityDropDown = {
    attach: function (context, settings) {
      var dropDown = '#post-visibility';

      $(dropDown  + ' + .dropdown-menu > li').click(function() {
        var setting = $('label > span', this).text();
        
        $('.text', dropDown).text(setting);

        if (setting == 'Community') {
          $('.material-icons', dropDown).text('group');
        }
        if (setting == 'Public') {
          $('.material-icons', dropDown).text('public');
        }

      });

    }
  };

 })(jQuery);
