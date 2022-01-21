/**
 * @file
 * Select-All Button functionality.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.group_views_bulk_operations = {
    attach: function (context, settings) {
      $('.vbo-view-form').once('group-vbo-init').each(Drupal.groupViewsBulkOperationsFrontUi);
    }
  };

  /**
   * Callback used in {@link Drupal.behaviors.group_views_bulk_operations}.
   */
  Drupal.groupViewsBulkOperationsFrontUi = function () {
    var $vboForm = $(this);

    // Add AJAX functionality to table checkboxes.
    var $multiSelectElement = $vboForm.find('.vbo-multipage-selector').first();
    if ($multiSelectElement.length && Drupal.viewsBulkOperationsSelection.display_id.length) {
      Drupal.viewsBulkOperationsSelection.display_id = Drupal.viewsBulkOperationsSelection.display_id + '/' + $multiSelectElement.data('group-id');
    }
  };

})(jQuery, Drupal);
