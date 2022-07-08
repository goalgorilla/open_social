/**
 * @file
 * Select-All Button functionality.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.social_views_bulk_operations = {
    attach: function (context, settings) {
      const $vboViewFormOnce = $(once('social-vbo-init', '.vbo-view-form', context));
      $vboViewFormOnce.each(Drupal.socialViewsBulkOperationsFrontUi);
    }
  };

  /**
   * Callback used in {@link Drupal.behaviors.social_views_bulk_operations}.
   */
  Drupal.socialViewsBulkOperationsFrontUi = function () {
    $('.vbo-view-form .select-all').addClass('form-no-label checkbox form-checkbox views-field-views-bulk-operations-bulk-form');
  };

})(jQuery, Drupal, once);
