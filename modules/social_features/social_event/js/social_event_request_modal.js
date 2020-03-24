/**
 * @file social_event_request_modal.js
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.eventEnrollmentRequest = {
    attach: function (context, settings) {

      // Trigger the modal window.
      $('body', context).once('eventEnrollmentRequest').each(function () {
        $('a#modal-trigger').click();

        // When the dialog closes, reload without the location.search parameter.
        $('body').on('dialogclose', '.ui-dialog', function() {
          location.assign(location.origin + location.pathname);
        });
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
