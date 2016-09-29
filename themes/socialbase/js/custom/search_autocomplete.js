(function ($) {

    // Initiate the Drupal autocomplete made in core/misc/autocomplete.js
    var autocomplete = Drupal.autocomplete;

    /**
     * Handles an autocomplete select event.
     *
     * @param {jQuery.Event} event
     *   The event triggered.
     * @param {object} ui
     *   The jQuery UI settings object.
     *
     * @return {bool}
     *   Returns false to indicate the event status.
     */
    function selectHandlerCustom(event, ui) {
        var terms = autocomplete.splitValues(event.target.value);
        // Remove the current input.
        terms.pop();
        // Add the selected item.
        if (ui.item.value.search(',') > 0) {
            terms.push('"' + ui.item.label + '"');
        }
        else {
            terms.push(ui.item.label);
        }
        event.target.value = terms.join(', ');
        // Return false to tell jQuery UI that we've filled in the value already.
        return false;
    }

    // Override the select handler initiated in core/misc/autocomplete.js by our custom one.
    Drupal.autocomplete.options.select = selectHandlerCustom;

})(jQuery);
