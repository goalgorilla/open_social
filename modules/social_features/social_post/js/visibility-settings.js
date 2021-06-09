/**
 * @file
 * Handles replacing the visible value of the picked visibility setting.
 */

(function ($) {

  'use strict';

   Drupal.behaviors.visibilityDropDown = {
      attach: function (context, settings) {
        var dropDown = '#post-visibility';

        function selectVisibility(e) {
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

          // Ensure selecting a visibility with they keyboard doesn't submit the
          // form.
          e.preventDefault();
        }

        // Limit keyboard selection to return.
        function withConfirmationKeys(fn) {
          return function (e) {
            if (e && e.keyCode === 32) {
              return fn.apply(this, e);
            }
          }
        }

        $(dropDown + ' + .dropdown-menu > .list-item')
          .keydown(withConfirmationKeys(selectVisibility))
          .click(selectVisibility);
      }
  };

})(jQuery);
