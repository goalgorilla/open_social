(function ($) {

  Drupal.behaviors.textareaAutogrow = {
    attach: function (context, settings) {

      // Attach autosize listener.
      autosize($('.form-control--autogrow'));
    }
  }

  Drupal.behaviors.textareaFocus = {
    attach: function (context, settings) {
      $('textarea:last').focusin(function() {
        $(this).parents('.main-container').toggleClass('open-keyboard');
      });
      $('textarea:last').focusout(function() {
        $(this).parents('.main-container').toggleClass('open-keyboard');
      });
    }
  }

})(jQuery);
