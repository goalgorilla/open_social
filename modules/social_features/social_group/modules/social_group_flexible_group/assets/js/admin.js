/**
 * @file
 * Flexible group functionality.
 */

(function ($, Drupal) {

  'use strict';

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

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.fieldGroupAllowedJoinMethods = {
    attach: function (context, settings) {
      // Initial.
      const groupVisibility = $('input[name="field_flexible_group_visibility"]:checked', context).val();

      if (groupVisibility == 'members') {
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
