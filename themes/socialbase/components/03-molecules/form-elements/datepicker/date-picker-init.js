(function ($) {

  Drupal.behaviors.initDatepicker = {
    attach: function (context, settings) {
      // Only for Desktop we switch the jquery datepicker.
      if (!isMobile()) {

        // Defaults
        var $context = $(context);

        var $time = $('.form-time');
        var $date = $('.form-date');
        // We want to use the native date picker
        // when the browser supports this on specific fields.
        if (browserSupportsDateInput()) {
          if (drupalSettings.socialbase != undefined &&
            drupalSettings.socialbase.datepicker.nativeDatePickFields != undefined) {
            // Get the fields which should use the native picker.
            var nativePicker = drupalSettings.socialbase.datepicker.nativeDatePickFields;
            if (nativePicker !== typeof undefined && nativePicker !== false) {
              $date.each(function (dateIndex) {
                $.each(nativePicker, function (index, value) {
                  var nameAttr = $(this).attr('name');
                  // If the name attribute contains the field name from
                  // the settings, remove it from the date array.
                  if (nameAttr !== typeof undefined && nameAttr !== false && nameAttr.includes(value)) {
                    $date.splice(dateIndex, 1);
                  }
                });
              });
            }
          }
        }

        // TIME
        $context.find($time).once('timePicker').each(function () {
          // Change it's input to text. Only for date element and only on Desktop.
          // If JS is disabled the fallback is the HTML 5 element, not too user friendly.
          $time.prop('type', 'text');
          // Initiate the datepicker element. So we can make it user friendly again.
          $time.timepicker({
            'show2400': false,
            'scrollDefault': 'now',
            'timeFormat': 'H:i',
            'step': 5
          });
          // Listen for changes in the time field and update the end value.
          $time.on('changeTime', function() {
            var endTime = $("#edit-field-event-date-end-0-value-time");
            if (!endTime.val()) endTime.val( $(this).val() );
          });
        });

        // DATES
        $context.find($date).once('datePicker').each(function () {
          // Set the prepoluted value of the datepicker for the end date
          var startDate = $("#edit-field-event-date-0-value-date");
          var endDate = $("#edit-field-event-date-end-0-value-date");
          // Change it's input to text. Only for date element and only on Desktop.
          // If JS is disabled the fallback is the HTML 5 element, not too user friendly.
          $date.prop('type', 'text');
          // Initiate the datepicker element. So we can make it user friendly again.
          $date.datepicker({
            altFormat: 'yy-mm-dd',
            dateFormat: 'yy-mm-dd', // @Todo we can alter this to show the user a different format.
            onSelect: function(dateText, inst) {

              // Check if end date field is empty and populate the target field
              if ( !endDate.val() ) {
                endDate.val( dateText )
              }
              // If the end date field is already set start comparing timestamps
              else {
                // Create timestamps to compare
                var startDateTimestamp = new Date(startDate[0].value).getTime();
                var endDateTimestamp = new Date(endDate[0].value).getTime();

                if (startDateTimestamp > endDateTimestamp) endDate.val( dateText );
                if (endDateTimestamp < startDateTimestamp) startDate.val( dateText );
              }
            },
            beforeShowDay: function(date) {
              // Create timestamps to compare
              var startDateTimestamp = new Date(startDate.val()).getTime() - 86400000; // Minus a day in ms
              var endDateTimestamp = new Date(endDate.val()).getTime();
              var currentDateTimestamp = date.getTime();

              if (currentDateTimestamp >= startDateTimestamp  && currentDateTimestamp <= endDateTimestamp) {
                return [true, 'bg-info', ''];
              }

              return [true, '', ''];
            }

          });

        });
        //
      }
    }
  }

  /**
   * Check if the browser supports [type='date'] input fields.
   *
   * See https://stackoverflow.com/a/10199306/576060
   */
  function browserSupportsDateInput() {
    var input = document.createElement('input');
    input.setAttribute('type','date');
    var notADateValue = 'not-a-date';
    input.setAttribute('value', notADateValue);
    return (input.value !== notADateValue);
  }

  function isMobile() {
    try { document.createEvent("TouchEvent"); return true; }
    catch(e) { return false; }
  }

})(jQuery);
