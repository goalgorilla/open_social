/**
 * @file
 * Translate default warning message of members field.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.validator = {
    attach: function (context, settings) {

      $('#edit-members').on('invalid', function () {
        this.setCustomValidity(settings.social_private_message.validator);
      });

    }
  }

})(jQuery, Drupal);
