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
                  $time.timepicker({
                    'show2400': false,
                    'scrollDefault': 'now',
                    'timeFormat': 'H:i'
                  });
                  // Listen for changes in the time field and update the end value.
                  $time.on('changeTime', function() {
                    var endTime = $("#edit-field-event-date-end-0-value-time");
                    if (!endTime.val()) endTime.val( $(this).val() );
                  });
                });

                // DATES
                $context.find($date).once('datePicker').each(function () {
                  // Change it's input to text. Only for date element and only on Desktop.
                  // If JS is disabled the fallback is the HTML 5 element, not too user friendly.
                  $date.prop('type', 'text');
                  // Initiate the datepicker element. So we can make it user friendly again.
                  $date.datepicker({
                      altFormat: 'yy-mm-dd',
                      dateFormat: 'yy-mm-dd', // @Todo we can alter this to show the user a different format.
                      onSelect: function(dateText, inst) {
                        // Set the prepoluted value of the datepicker for the end date
                        var startDate = $("#edit-field-event-date-0-value-date");
                        var endDate = $("#edit-field-event-date-end-0-value-date");

                        // Check if end date field is empty and populate the target field
                        if ( !endDate.val() ) {
                          endDate.val( dateText )
                        }
                        // If the end date field is already set do this
                        else {
                          // Create timestamps to compare
                          var startDateTimestamp = new Date(startDate[0].value).getTime();
                          var endDateTimestamp = new Date(endDate[0].value).getTime();

                          console.log(startDateTimestamp);
                          console.log(endDateTimestamp);

                          // If the start user selects a start date that exceeds the end date do this
                          if (startDateTimestamp > endDateTimestamp ) {
                            endDate.val( dateText )
                          }
                        }

                        console.log( endDate[0].value );

                      }
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
