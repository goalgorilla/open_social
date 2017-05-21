/**
 * @file
 * replaces the placeholder of post when a photo is uploaded.
 */

(function ($) {

  'use strict';

  // Change placeholder text when someone adds a photo.
  Drupal.behaviors.postPlaceholder = {
    attach: function (context, settings) {

      // Process this field only once
      console.log('script init');

      $(document, context).once('field-post-image-add').on('click', '#post-photo-add', function(e) {
        console.log('add image');
        $('#edit-field-post-image-0-upload').trigger('click');
        e.preventDefault();
      });

      $(document, context).once('field-post-image-remove').on('click', '#post-photo-remove', function(e) {
        console.log('remove image');
        $('button[name="field_post_image_0_remove_button"').trigger('click');
        e.preventDefault();
      });

      $('#edit-field-post-image-0-upload').change(function(e) {
        $('#edit-field-post-0-value').attr("placeholder", Drupal.t('Say something about this photo'));
      });

    }
  };
})(jQuery);
