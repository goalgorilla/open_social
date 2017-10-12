(function ($) {

  Drupal.behaviors.fitFrame = {
    attach: function (context, settings) {

      // Attach autosize listener.
      $('.iframe-container').fitFrame({
        mode: 'resize',
        fitHeight: true
      });
    }
  }

})(jQuery);
