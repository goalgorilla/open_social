/**
 * @file
 * Defines the custom behaviors needed for cropper integration.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Updates the summary of the wrapper.
   */
  Drupal.ImageWidgetCropType.prototype.updateSummary = function () {
    return '';
  };

  Drupal.behaviors.socialBaseImageWidgetCrop = {
    attach: function(context, drupalSettings) {
      // Open widget when file is uploaded.
      $('.image-widget-data').each(function (i, e) {
        if (!$('> .form-file', e).length && !$(e).data('crop-attached')) {
          $(e).parent().next('.image-data__crop-wrapper').attr('open', 'open');
          $(e).data('crop-attached', true);
        }
        else if ($('> .form-file', e).length && $(e).data('crop-attached')) {
          $(e).data('crop-attached', false);
        }
      });
    }
  };

  $('.image-widget-data').each(function (i, e) {
    if (!$('> .form-file', e).length) {
      $(e).data('crop-attached', true);
    }
  });

  delete Drupal.behaviors.imageWidgetCrop.detach;

})(jQuery, Drupal, drupalSettings);
