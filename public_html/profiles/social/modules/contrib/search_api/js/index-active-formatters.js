/**
 * @file
 * Attaches show/hide functionality to checkboxes in the "Processor" tab.
 */

(function ($) {

  "use strict";

  Drupal.behaviors.searchApiIndexFormatter = {
    attach: function (context, settings) {
      $('.search-api-status-wrapper input.form-checkbox', context).each(function () {
        var $checkbox = $(this);
        var processor_id = $checkbox.data('id');

        var $rows = $('.search-api-processor-weight--' + processor_id, context);
        var tab = $('.search-api-processor-settings-' + processor_id, context).data('verticalTab');

        // Bind a click handler to this checkbox to conditionally show and hide
        // the processor's table row and vertical tab pane.
        $checkbox.on('click.searchApiUpdate', function () {
          if ($checkbox.is(':checked')) {
            $rows.show();
            if (tab) {
              tab.tabShow().updateSummary();
            }
          }
          else {
            $rows.hide();
            if (tab) {
              tab.tabHide().updateSummary();
            }
          }
        });

        // Attach summary for configurable items (only for screen-readers).
        if (tab) {
          tab.details.drupalSetSummary(function () {
            return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
          });
        }

        // Trigger our bound click handler to update elements to initial state.
        $checkbox.triggerHandler('click.searchApiUpdate');
      });
    }
  };

})(jQuery);
