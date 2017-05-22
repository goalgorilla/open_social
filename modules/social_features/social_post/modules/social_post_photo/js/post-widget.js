/**
 * @file
 * extends the image widget width eventlisteners and triggers for customized presentation.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.postPhotoWidget = {
    attach: function (context, settings) {

      $(document, context).once('field-post-image-add').on('click', '#post-photo-add', function(e) {
        $('input[data-drupal-selector="edit-field-post-image-0-upload"]').trigger('click');
        e.preventDefault();
      });

      $(document, context).once('field-post-image-remove').on('click', '#post-photo-remove', function(e) {
        $('button[data-drupal-selector="edit-field-post-image-0-remove-button"]').trigger('mousedown');
        e.preventDefault();
      });

      // Change placeholder text when someone adds a photo.
      $('#edit-field-post-image-0-upload').change(function(e) {
        $('#edit-field-post-0-value').attr("placeholder", Drupal.t('Say something about this photo'));
      });

    }
  };
})(jQuery);
