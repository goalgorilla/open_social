/**
 * @file
 * extends the image widget width eventlisteners and triggers for customized presentation.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.postPhotoWidget = {
    attach: function (context, settings) {

      $(document, context).once('field-post-image-add').on('click', '#post-photo-add', function(e) {
        $('input[data-drupal-selector^="edit-field-post-image-0-upload"]', context).trigger('click');
        e.preventDefault();
      });

      $(document, context).once('field-post-image-remove').on('click', '#post-photo-remove', function(e) {
        $('button[data-drupal-selector^="edit-field-post-image-0-remove-button"]', context).trigger('mousedown');
        e.preventDefault();
      });

      // Change placeholder text when someone adds a photo.
      $('[data-drupal-selector^="edit-field-post-image-0-upload"]').change(function(e) {
        $('#edit-field-post-0-value', context).attr("placeholder", Drupal.t('Say something about this photo'));
        $('[data-drupal-selector^="edit-field-post-image-wrapper"] .spinner', context).remove();
        $('[data-drupal-selector^="edit-field-post-image-wrapper"] .form-group .form-group', context).prepend('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>');
      });

    }
  };
})(jQuery);
