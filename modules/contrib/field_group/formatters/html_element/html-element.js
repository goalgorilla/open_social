(function ($) {

  'use strict';

  /**
   * Implements Drupal.FieldGroup.processHook().
   */
  Drupal.FieldGroup.Effects.processHtml_element = {
    execute: function (context, settings, type) {

      $('.fieldgroup-collapsible', context).once('fieldgroup-effects').each(function () {
        var $wrapper = $(this);

        // Turn the legend into a clickable link, but retain span.field-group-format-toggler
        // for CSS positioning.

        var $toggler = $('.field-group-toggler:first', $wrapper);
        var $link = $('<a class="field-group-title" href="#"></a>');
        $link.prepend($toggler.contents());

        // Add required field markers if needed
        if ($(this).is('.required-fields') && $(this).find('.form-required').length > 0) {
          $link.append(' ').append($('.form-required').eq(0).clone());
        }

        $link.appendTo($toggler);

        // .wrapInner() does not retain bound events.
        $link.click(function () {
          var wrapper = $wrapper.get(0);
          // Don't animate multiple times.
          if (!wrapper.animating) {
            wrapper.animating = true;
            var speed = $wrapper.hasClass('speed-fast') ? 300 : 1000;
            if ($wrapper.hasClass('effect-none') && $wrapper.hasClass('speed-none')) {
              $('> .field-group-wrapper', wrapper).toggle();
            }
            else if ($wrapper.hasClass('effect-blind')) {
              $('> .field-group-wrapper', wrapper).toggle('blind', {}, speed);
            }
            else {
              $('> .field-group-wrapper', wrapper).toggle(speed);
            }
            wrapper.animating = false;
          }
          $wrapper.toggleClass('collapsed');
          return false;
        });

      });
    }
  };

})(jQuery);
