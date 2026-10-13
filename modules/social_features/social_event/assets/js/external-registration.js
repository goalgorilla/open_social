/**
 * @file
 * Keeps native enrolment settings off while external registration is selected.
 */

(function (Drupal, once) {

  /**
   * Toggles native enrolment together with external registration.
   *
   * Native enrolment is unchecked while external registration is enabled, and
   * restored when it is disabled again. Saved external events store native
   * enrolment as off, so in that case it is restored to the field default (on).
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.socialEventExternalRegistration = {
    attach(context) {
      once('social-event-external-registration', '[name="field_external_registration[value]"]', context).forEach((externalRegistration) => {
        const enrollment = document.querySelector('[name="field_event_enroll[value]"]');
        if (!(externalRegistration instanceof HTMLInputElement) || !(enrollment instanceof HTMLInputElement)) {
          return;
        }

        let enrollmentBeforeExternal = externalRegistration.checked ? true : enrollment.checked;

        const setEnrollment = (checked) => {
          if (enrollment.checked !== checked) {
            enrollment.checked = checked;
            enrollment.dispatchEvent(new Event('change', { bubbles: true }));
          }
        };

        externalRegistration.addEventListener('change', () => {
          if (externalRegistration.checked) {
            enrollmentBeforeExternal = enrollment.checked;
            setEnrollment(false);
          }
          else {
            setEnrollment(enrollmentBeforeExternal);
          }
        });

        if (externalRegistration.checked) {
          setEnrollment(false);
        }
      });
    },
  };

})(Drupal, once);
