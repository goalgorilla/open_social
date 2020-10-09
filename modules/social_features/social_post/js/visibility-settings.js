/**
 * @file
 * Handles replacing the visible value of the picked visibility setting.
 */

(function ($) {

  'use strict';

   Drupal.behaviors.visibilityDropDown = {
      attach: function (context, settings) {
        var dropDown = '#post-visibility';

        $(dropDown + ' + .dropdown-menu > .list-item').click(function() {
          var label = $('label > span', this).first().text();
          var icon = $('svg use', this).first().attr('xlink:href');

          // Show the currently selected text and icon on the button.
          $('.text', dropDown).text(label);
          $('.btnicon', dropDown).attr('xlink:href', icon);

          // Find all the inputs and uncheck them.
          $('input', dropDown).prop("checked", false);
          // Just check the input below the list item we clicked.
          $(this).find('input').prop("checked", true);

          $(this).siblings('li').removeClass('list-item--active');
          $(this).addClass('list-item--active');

        });

      }
  };

})(jQuery);
