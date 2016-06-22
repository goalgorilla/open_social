/**
 * @file
 */

(function ($) {

    /**
     * Behaviors.
     */
    Drupal.behaviors.socialEventFormSubmit = {
        attach: function (context, settings) {
            // Submit form on anchor click.
            $('a.enroll-form-submit').click(function(e) {
                e.preventDefault();

                // Submit my parent form.
                $(this).closest('form').submit();

                return false;
            });
        }
  };

})(jQuery);
