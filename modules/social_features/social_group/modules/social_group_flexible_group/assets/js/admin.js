/**
 * @file
 * Flexible group functionality.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.fieldGroupAllowedJoinMethods = {
    attach: function (context, settings) {

      // Sets the invite only option and disables the rest.
      function setInviteJoinMethod(context) {
        $('input[name="field_group_allowed_join_method"]', context)
          .filter('[value="added"]')
          .click();

        $('input[name="field_group_allowed_join_method"]', context)
          .filter('[value="request"]')
          .attr('disabled', true);

        $('input[name="field_group_allowed_join_method"]', context)
          .filter('[value="direct"]')
          .attr('disabled', true);
      }

      // Initial.
      const groupVisibility = $('input[name="field_flexible_group_visibility"]:checked', context).val();

      // If we don't have any existing values, then default to request.
      // If we have members selected, then make sure the other options are
      // disabled.
      if (!groupVisibility) {
        $('input[name="field_group_allowed_join_method"]', context)
          .filter('[value="request"]')
          .click();
      }
      else if (groupVisibility == 'members') {
        setInviteJoinMethod(context);
      }

      // On change event.
      $('input[name="field_flexible_group_visibility"]', context).change(function() {
        const groupVisibility = $('input[name="field_flexible_group_visibility"]:checked', context).val();

        if (groupVisibility == 'members') {
          setInviteJoinMethod(context);
          return;
        }

        $('input[name="field_group_allowed_join_method"]', context)
          .filter('[value="request"]')
          .attr('disabled', false);

        $('input[name="field_group_allowed_join_method"]', context)
          .filter('[value="direct"]')
          .attr('disabled', false);
      });
    }
  };

})(jQuery, Drupal);
