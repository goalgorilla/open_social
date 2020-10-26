/**
 * @file
 * Handles replacing the visible value of the picked visibility setting
 * for the dropdown--slick the other uses visibility-settings.js.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.visibilityDropDownSlickSelect = {
    attach: function (context, settings) {
      var $context = $(context);

      function selectVisibility(e) {
        var dropDown = $(this).parentsUntil('.slick-select-wrapper').find('.dropdown-toggle');
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

      // $(dropDown + ' + .dropdown-menu > .list-item')
      //   .keydown(withConfirmationKeys(selectVisibility))
      //   .click(selectVisibility);



      $context.find('.slick-select-wrapper .dropdown-menu > .list-item').each(function () {
        // $(this).on('keydown', function () {
        //   withConfirmationKeys(selectVisibility)
        // });
        // $(this).on('click', function () {
        //   selectVisibility();
        // });

        $('.slick-select-wrapper + .dropdown-menu > .list-item')
          .keydown(withConfirmationKeys(selectVisibility))
          .click(selectVisibility);
      });
    }
  };

})(jQuery);
