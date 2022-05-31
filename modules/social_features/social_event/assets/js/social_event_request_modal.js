/**
 * @file social_event_request_modal.js
 */

(function ($, Drupal, drupalSettings, once) {

  'use strict';

  Drupal.behaviors.eventEnrollmentRequest = {
    attach: function (context, settings) {

      // Trigger the modal window.
      const $bodyElementOnce = $(once('eventEnrollmentRequest', 'body', context));
      $bodyElementOnce.each(function () {
        $('a#modal-trigger').click();

        // When the dialog closes, reload without the location.search parameter.
        $('body').on('dialogclose', '.ui-dialog', function() {
          location.assign(location.origin + location.pathname);
        });
      });

      // When submitting the request, close the page.
      var closeDialog = settings.eventEnrollmentRequest.closeDialog;

      $(once('eventEnrollmentSubmitRequest', 'body'))
        .on('dialogclose', '.ui-dialog', function() {
          if (closeDialog === true) {
            location.assign(location.origin + location.pathname);
          }
        });
    }
  }

})(jQuery, Drupal, drupalSettings, once);
