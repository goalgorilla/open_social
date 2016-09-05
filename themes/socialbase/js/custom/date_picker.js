(function ($) {

    Drupal.behaviors.initDatepicker = {
        attach: function (context, settings) {
            // Only for Desktop we switch the jquery datepicker.
            if (!isMobile()) {
                console.log("kaas");
                var $time = $('.form-time');
                var $date = $('.form-date');

                // Check whether we have a form element time.
                if ($time.length) {
                    // Change it's input to text. Only for date element and only on Desktop.
                    // If JS is disabled the fallback is the HTML 5 element, not too user friendly.
                    $time.prop('type', 'text');
                }
                // Check whether we have a form element date.
                if ($date.length) {
                    // Change it's input to text. Only for date element and only on Desktop.
                    // If JS is disabled the fallback is the HTML 5 element, not too user friendly.
                    $date.prop('type', 'text');
                    // Initiate the datepicker element. So we can make it user friendly again.
                    $date.datepicker({
                        altFormat: 'yy-mm-dd',
                        dateFormat: 'yy-mm-dd'
                    });
                }
            }
        }
    }

    function isMobile() {
        try { document.createEvent("TouchEvent"); return true; }
        catch(e) { return false; }
    }

})(jQuery);
