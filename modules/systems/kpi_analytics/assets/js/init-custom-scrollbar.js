(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.initCustomScrollBar = {
    attach: function () {

      $(window).on("load",function(){
        var chartBlock = $('.chart-block');
        var chartBlockWrapper = chartBlock.find('.chart-block--wrapper');
        var svgBlockWidth = chartBlockWrapper.find('svg').width();

        chartBlockWrapper.css('width', svgBlockWidth);

        chartBlock.mCustomScrollbar({
          axis: 'x',
          theme: 'dark-thin',
          autoExpandScrollbar: true,
          advanced: {
            autoExpandHorizontalScroll: true
          }
        });
      });
    }
  };

})(jQuery, Drupal);