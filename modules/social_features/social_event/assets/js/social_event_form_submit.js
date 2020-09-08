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

                // Set the decline operator value.
                if($('input[name="operation"]').length) {
                    $('input[name="operation"]').val('decline');
                }

                // Submit my parent form.
                $(this).closest('form').submit();

                return false;
            });
        }
  };

})(jQuery);
