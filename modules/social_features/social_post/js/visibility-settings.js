/**
 * @file
 * Handles replacing the visible value of the picked visibility setting.
 */

(function ($) {

  'use strict';

   Drupal.behaviors.visibilityDropDown = {
      attach: function (context, settings) {
        var dropDown = '#post-visibility';

        $(dropDown + ' + .dropdown-menu > .list-group-item').click(function() {
          var setting = $('label > span', this).text();

          $('.text', dropDown).text(setting);

          if (setting == 'Community') {
            $('#btnicon', dropDown).attr('xlink:href', '#icon-community');
          }
          if (setting == 'Public') {
            $('#btnicon', dropDown).attr('xlink:href', '#icon-public');
          }

          // Find all the inputs and uncheck them.
          $(dropDown).find('input').prop("checked", false);
          // Just check the input below the list item we clicked.
          $(this).find('input').prop("checked", true);

          $(this).siblings('li').removeClass('active');
          $(this).addClass('active');

        });

      }
  };

})(jQuery);
