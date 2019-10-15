/**
 * @file
 * Enable CTRL+Enter to submit a form.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.keycodeSubmit = {
    attach: function (context, settings) {

      // Post form.
        $('#social-post-entity-form .form-textarea')
      .off('keydown', onTextAreaKeyDown)
      .on('keydown', onTextAreaKeyDown);
      // comment form
        $('.comment-form .form-textarea').on('keydown')
      .off('keydown', onTextAreaKeyDown)
      .on('keydown', onTextAreaKeyDown);
    }
  };
  function onTextAreaKeyDown(e) {
    var textarea = $(this);
    var submit = textarea.closest('form').find('.form-submit');
    if ($.trim(textarea.val()) != '') {
      if ((e.keyCode === 13 && e.ctrlKey) || (e.keyCode === 13 && e.metaKey)) {
        e.preventDefault();
        submit.trigger('click');
      }
    }
  }
})(jQuery);
