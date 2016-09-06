(function ($) {

    Drupal.behaviors.initDatepicker = {
        attach: function (context, settings) {
            // Only for Desktop we switch the jquery datepicker.
            if (!isMobile()) {

                // Defaults
                var $context = $(context);

                var $time = $('.form-time');
                var $date = $('.form-date');

                // TIME
                $context.find($time).once('timePicker').each(function () {
                  // Change it's input to text. Only for date element and only on Desktop.
                  // If JS is disabled the fallback is the HTML 5 element, not too user friendly.
                  $time.prop('type', 'text');
                  // Initiate the datepicker element. So we can make it user friendly again.
                  var options = {
                    'show2400': true,
                    'scrollDefault': 'now',
                    'timeFormat': 'H:i'
                  };
                  $time.timepicker(options);
                });

                // DATES
                $context.find($date).once('datePicker').each(function () {
                  // Change it's input to text. Only for date element and only on Desktop.
                  // If JS is disabled the fallback is the HTML 5 element, not too user friendly.
                  $date.prop('type', 'text');
                  // Initiate the datepicker element. So we can make it user friendly again.
                  $date.datepicker({
                      altFormat: 'yy-mm-dd',
                      dateFormat: 'yy-mm-dd' // @Todo we can alter this to show the user a different format.
                  });
                });

            }
        }
    }

    function isMobile() {
        try { document.createEvent("TouchEvent"); return true; }
        catch(e) { return false; }
    }

})(jQuery);
