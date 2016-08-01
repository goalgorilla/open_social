/**
 * @file
 * Enable CTRL+Enter to submit a form.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.keycodeSubmit = {
    attach: function (context, settings) {

      // Enable CTRL+Enter to submit a form.
      var keySubmit = function (form, textarea, submit) {
        textarea.on("keydown", function (e) {
          // Make sure the textarea contains text.
          if ($.trim(textarea.val()) != "") {
            if (e.keyCode == 13 && e.ctrlKey) {
              e.preventDefault();
              submit.prop('disabled', true);
              form.submit();
            }
          }
        });
      }

      // Post form.
      var postForm = $('#social-post-entity-form');
      keySubmit($(postForm), $('.form-textarea', postForm), $('.form-submit', postForm));

      // Comment forms.
      $('.comment-form').each(function () {
        keySubmit($(this), $('.form-textarea', this), $('.form-submit', this));
      });

    }
  };
})(jQuery);
