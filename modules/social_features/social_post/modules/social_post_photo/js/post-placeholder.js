/**
 * @file
 * replaces the placeholder of post when a photo is uploaded.
 */

(function ($) {

  'use strict';

  // Change placeholder text when someone adds a photo.
  Drupal.behaviors.postPlaceholder = {
    attach: function (context, settings) {
      $('#edit-field-post-image-0-upload').change(function(e) {
        $('#edit-field-post-0-value').attr("placeholder", Drupal.t('Say something about this photo'));
      });
    }
  };
})(jQuery);
