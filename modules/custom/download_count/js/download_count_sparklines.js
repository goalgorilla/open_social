/**
 * @file
 */

(function ($) {
  Drupal.behaviors.download_count_sparklines = {
    attach: function(context, settings) {
      var options = {
        type: settings.download_count.type,
        chartRangeMin: settings.download_count.min,
        height: settings.download_count.height,
        width: settings.download_count.width,
      };
      $('div.download-count-sparkline-daily').sparkline(settings.download_count.values.daily.split(','), options);
      $('div.download-count-sparkline-weekly').sparkline(settings.download_count.values.weekly.split(','), options);
      $('div.download-count-sparkline-monthly').sparkline(settings.download_count.values.monthly.split(','), options);
      $('div.download-count-sparkline-yearly').sparkline(settings.download_count.values.yearly.split(','), options);
    }
  }
})(jQuery);
