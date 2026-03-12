/**
 * @file
 * Prevents form submission while AJAX requests are in progress.
 *
 * Workaround for Drupal core issue #1736308: when #ajax is triggered on
 * "blur", the AJAX system disables the triggering element. If the user clicks
 * Submit at the same moment, the disabled field's value is excluded from the
 * POST data and silently lost.
 *
 * @see https://www.drupal.org/project/drupal/issues/1736308
 */

((Drupal, once) => {
  'use strict';

  Drupal.behaviors.eventMeetingAjaxGuard = {
    attach(context) {
      once('ajax-guard', '[data-ajax-guard]', context).forEach((form) => {
        let ajaxInProgress = false;
        let pendingSubmit = null;

        // jQuery global AJAX events fire on document, not on individual
        // elements. Filter by checking if the triggering element belongs
        // to this form.
        jQuery(document).on('ajaxSend', (event, jqXHR, settings) => {
          if (settings.extraData && form.contains(
            document.querySelector(`[name="${CSS.escape(settings.extraData._triggering_element_name)}"]`)
          )) {
            ajaxInProgress = true;
          }
        });

        jQuery(document).on('ajaxComplete', (event, jqXHR, settings) => {
          if (!ajaxInProgress) {
            return;
          }

          if (settings.extraData && form.contains(
            document.querySelector(`[name="${CSS.escape(settings.extraData._triggering_element_name)}"]`)
          )) {
            ajaxInProgress = false;

            // If a submit was deferred, replay it now.
            if (pendingSubmit) {
              const submitter = pendingSubmit;
              pendingSubmit = null;
              // Use requestAnimationFrame to let the AJAX cleanup finish
              // (re-enable disabled fields, remove throbbers, etc.).
              requestAnimationFrame(() => {
                if (submitter) {
                  submitter.click();
                }
                else {
                  form.requestSubmit();
                }
              });
            }
          }
        });

        form.addEventListener('submit', (e) => {
          if (ajaxInProgress) {
            e.preventDefault();
            e.stopImmediatePropagation();
            // Remember which button was clicked so we preserve its value.
            pendingSubmit = e.submitter || null;
          }
        }, true); // Capture phase to intercept before Drupal's handlers.
      });
    },
  };
})(Drupal, once);
