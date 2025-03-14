/**
 * @file
 */

(function ($, Drupal) {

  'use strict';

  // Check on Group settings for correct states.
  Drupal.behaviors.defaultGroupStates = {
    attach: function (context, settings) {
      var scope = '#edit-group-type';

      // Uncheck all the create groups if LU is not able to add groups.
      $('#edit-permissions-allow-group-create').on('click', function () {
        if (!$(this).prop('checked')) {
          $('input', scope).each(function (e) {
            $(this).prop('checked', false);
          });
        }
      });
    }
  };

})(jQuery, Drupal);
