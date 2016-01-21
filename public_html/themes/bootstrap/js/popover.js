/**
 * @file
 * Bootstrap Popovers.
 */

var Drupal = Drupal || {};

(function ($, Drupal, Bootstrap) {
  "use strict";

  /**
   * Extend the Bootstrap Popover plugin constructor class.
   */
  Bootstrap.extendPlugin('popover', function (settings) {
    return {
      DEFAULTS: {
        animation: !!settings.popover_animation,
        html: !!settings.popover_html,
        placement: settings.popover_placement,
        selector: settings.popover_selector,
        trigger: _.filter(_.values(settings.popover_trigger)).join(' '),
        triggerAutoclose: !!settings.popover_trigger_autoclose,
        title: settings.popover_title,
        content: settings.popover_content,
        delay: parseInt(settings.popover_delay, 10),
        container: settings.popover_container
      }
    };
  });

  /**
   * Bootstrap Popovers.
   *
   * @todo This should really be properly delegated if selector option is set.
   */
  Drupal.behaviors.bootstrapPopovers = {
    attach: function (context) {
      var $currentPopover = $();

      if ($.fn.popover.Constructor.DEFAULTS.triggerAutoclose) {
        $(document).on('click', function (e) {
          if ($currentPopover.length && !$(e.target).is('[data-toggle=popover]') && $(e.target).parents('.popover.in').length === 0) {
            $currentPopover.popover('hide');
            $currentPopover = $();
          }
        });
      }
      var elements = $(context).find('[data-toggle=popover]').toArray();
      for (var i = 0; i < elements.length; i++) {
        var $element = $(elements[i]);
        var options = $.extend({}, $.fn.popover.Constructor.DEFAULTS, $element.data());
        if (!options.content) {
          options.content = function () {
            var target = $(this).data('target');
            return target && $(target) && $(target).length && $(target).clone().removeClass('visually-hidden').wrap('<div/>').parent()[$(this).data('bs.popover').options.html ? 'html' : 'text']() || '';
          }
        }
        $element.popover(options).on('click', function (e) {
          e.preventDefault();
        });
        if (options.triggerAutoclose) {
          $element.on('show.bs.popover', function () {
            if ($currentPopover.length) {
              $currentPopover.popover('hide');
            }
            $currentPopover = $(this);
          });
        }
      }
    }
  };

})(jQuery, Drupal, Drupal.Bootstrap);
