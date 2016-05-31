/**
 * @file
 * Adds functionality to copy path to a specific part of the array / object.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.searchKintTrail = {
    attach: function (context, settings) {

      var first = true;
      $('.kint dt', context).once('trail').each(function () {

        if (first) {
          first = false;
          return true;
        }

        $(this).append('<span class="kint-get-path"><a href="#">' + Drupal.t('Get path') + '</a></span>');
      });

      $('.kint-get-path a').once().click(function (e) {
        e.preventDefault();

        var pathItems = [];
        buildPath($(this).parents('dl').first(), pathItems);
        pathItems.reverse();

        var path = '$var';
        $.each(pathItems, function (index, item) {
          if (item.label === '$args') {
            return true;
          }
          var prevItem = pathItems[index - 1];
          if (prevItem.type === 'array') {
            path += '[' + item.label + ']';
          }
          else if (prevItem.type.substring(0, 7) === 'object ') {
            path += '->' + item.label;
          }
        });

        $(this).addClass('hidden');
        $(this).after('<input id="kint-path-value" type="text" value="' + path + '" />');
        $('#kint-path-value').select().blur(function () {
          $(this).remove();
          $('.kint-get-path a.hidden').removeClass('hidden');
        });

      });

      function buildPath($current, pathItems) {
        if (!$current.length) {
          return;
        }
        var label = $current.find('> dt dfn').text().trim();

        // If uppermost dl set label to $args so it gets included.
        if (!$current.parents('dl').length) {
          label = '$args';
        }

        if (label.length) {
          pathItems.push({
            type: $current.find('> dt var').text(),
            index: $current.index(),
            label: label
          });
        }
        buildPath($current.parents('dl').first(), pathItems);

      }

    }
  };
})(jQuery);
