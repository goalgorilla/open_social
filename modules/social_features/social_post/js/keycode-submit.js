/**
 * @file
 * Enable CTRL+Enter to submit a form.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.keycodeSubmit = {
    onTextAreaKeyDown: function (e) {
      // Check if the return key is pressed together with CTRL or a meta key.
      // The meta key is Command (⌘) on Mac or the Windows key on Windows.
      if (e.which === 13 && (e.ctrlKey || e.metaKey)) {
        var $textarea = $(e.target);
        // If we actually have a value then we submit the form this textarea
        // belongs to.
        if ($textarea.val().trim().length) {
          e.preventDefault();
          var $form = $textarea.closest('form');
          var $submit = $form.find('.form-submit');

          // If there is a form submit button then we click it. On some of the
          // post forms this has some side effects that are required for a
          // successful submission.
          if ($submit.length) {
            // If it's a submit button, used by Ajax comments
            // the click event is canceled by ajax_comments, see
            // e.preventDefault() in ajax_comments.js.
            // So we use the mousedown event in that specific case.
            if ($textarea.parents('.ajax-comments-form-add').length) {
              $submit.mousedown();
            }
            else {
              $submit.click();
            }
          }
          // If a submit button isn't found we fall back to submitting the form
          // outright.
          else {
            $form.submit();
          }

        }
      }
    },

    attach: function (context, settings) {
      // Add our keyDown handler to all textarea's that were attached.
      $('#social-post-entity-form .form-textarea, .comment-form .form-textarea', context).keydown(Drupal.behaviors.keycodeSubmit.onTextAreaKeyDown);
    }
  };
})(jQuery);
