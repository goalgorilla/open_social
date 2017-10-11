(function ($) {

  Drupal.behaviors.textareaAutogrow = {
    attach: function (context, settings) {

      // Attach autosize listener.
      autosize($('.form-control--autogrow'));
    }
  }

})(jQuery);
