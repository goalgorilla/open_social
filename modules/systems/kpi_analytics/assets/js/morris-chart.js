(function ($, Drupal, once) {

  'use strict';

  Drupal.behaviors.kpiAnalyticsRenderMorris = {
    attach: function (context, settings) {
      once("renderChart", "div.morris_chart").forEach(function (morris_chart_element) {
        var uuid = $(morris_chart_element).attr('id'),
          options = settings.kpi_analytics.morris.chart[uuid].options;

        if (!options.plugin) {
          options.plugin = 'Line';
        }

        options.xLabelFormat = function (x) {
          if (!options.horizontal) {
            return {
              'label': x.label,
              'highlight': !!x.src.highlight
            };
          }
          return x.label;
        };

        var Morris = $.extend(true, {}, window.Morris);

        Morris[options.plugin].prototype.drawXAxisLabel = function (xPos, yPos, text) {
          var element;
          var xText = text;

          if (!this.options.horizontal) {
            xText = text.label;
          }
          else {
            addDiffText(this);
          }
          element = this.raphael.text(xPos, yPos, xText)
            .attr('font-size', this.options.gridTextSize)
            .attr('font-family', this.options.gridTextFamily)
            .attr('font-weight', this.options.gridTextWeight)
            .attr('fill', this.options.gridTextColor);

          if (text.highlight) {
            element.attr('class', 'morris-label-highlight');
          }

          return element;
        };

        Morris[options.plugin].prototype.hoverContentForRow = function (index) {
          var j, y;
          var row = this.data[index];

          var $content = $('<div class="morris-hover-row-label">').html(row.label.label);

          if (row.label.highlight) {
            $content.addClass('morris-label-highlight');
          }

          var content = $content.prop('outerHTML');

          for (j in row.y) {
            y = row.y[j];

            if (!this.options.labels[j]) {
              continue;
            }

            content += '<div class="morris-hover-point">' +
              '<span class="morris-hover-marker" style="background-color: ' + this.colorFor(row, j, 'label') + '"></span>' +
              this.options.labels[j] + ': ' +
              this.yLabelFormat(y, j) +
              '</div>';
          }

          if (typeof this.options.hoverCallback === 'function') {
            content = this.options.hoverCallback(index, this.options, content, row.src);
          }

          return [content, row._x, row._ymax];
        };

        if (options.horizontal) {
          $('#' + uuid)
            .height(options.data.length * 60)
            .width(1000);
        }

        var chart = Morris[options.plugin](options);
        if (options.hideHover) {
          $('#' + uuid).find('.morris-hover').remove();
        }

        if (options.horizontal) {
          addDiffText(chart);
          $(window).resize(function () {
            chart.options.isRenderedDiff = false;
          })
        }

      });
    }
  };

  /**
   * Render changes on the chart.
   *
   * @param chart
   *   Morris char.
   */
  function addDiffText(chart) {
    if (typeof chart === 'undefined') {
      return;
    }

    // Check if labels exists.
    if (
      typeof chart.data[0].label_x === 'undefined' ||
      typeof chart.data[0].label_y === 'undefined'
    ) {
      return;
    }

    var data = chart.data,
      options = chart.options;

    // Check if already rendered.
    if (options.isRenderedDiff) {
      return;
    }

    // Render changed value for every bar.
    data.forEach(function (item) {
      // Set static variables.
      var diff = item.src.difference,
        text = Math.abs(diff),
        curr = item.src.current,
        dx = curr.length * 5 + 20,
        textDx = dx,
        x = item.label_x[0],
        y = item.label_y[0],
        color = '#777',
        rectColor = '#f3f3f3',
        rectWidthChange = 45,
        rectStrokeColor = '#e6e6e6',
        triangleX1, triangleY1, triangleX2, triangleY2, triangleX3, triangleY3;

      // Set dynamic variables.
      if (diff == 0) {
        text = Drupal.t('no change');
        rectWidthChange = 20;
        textDx += 10;
      }
      else if (diff < 0) {
        color = '#a94442'
        rectColor = '#f2dede';
        textDx += 35;
        rectStrokeColor = '#ebccd1'
      }
      else {
        color = '#3c763d'
        rectColor = '#dff0d8';
        textDx += 35;
        rectStrokeColor = '#d6e9c6'
      }

      // Render changed values.
      var svgText = chart.raphael.text(x + textDx, y, text)
        .attr('font-size', options.gridTextSize)
        .attr('text-anchor', 'start')
        .attr('font-family', options.gridTextFamily)
        .attr('font-weight', 'bold')
        .attr('fill', color);

      // Set coordinates and sizes of rectangles.
      var svgTextBox = svgText.getBBox(),
        svgTextHeight = svgTextBox.height,
        svgTextWidth = svgTextBox.width,
        rectWidth = svgTextWidth + rectWidthChange,
        rectHeight = svgTextHeight + 12;

      // Set coordinates for triangles.
      triangleX1 = x + textDx - 11;
      triangleX2 = triangleX1 - 12;
      triangleX3 = (triangleX1 + triangleX2) / 2;
      if (diff < 0) {
        triangleY1 = y - svgTextHeight / 2 + 3;
        triangleY3 = triangleY1 + svgTextHeight - 6;
      }
      else if (diff > 0) {
        triangleY1 = y + svgTextHeight / 2 - 3;
        triangleY3 = triangleY1 - svgTextHeight + 6;
      }
      triangleY2 = triangleY1;

      // Render rectangle.
      chart.raphael.rect(x + dx, y - rectHeight / 2, rectWidth, rectHeight, 5)
        .attr('stroke', rectStrokeColor)
        .attr('fill', rectColor)
        .toBack()

      // Render triangle.
      if (diff != 0) {
        var path = [
          'M',
          triangleX1,
          triangleY1,
          'L',
          triangleX2,
          triangleY2,
          'L',
          triangleX3,
          triangleY3,
          'Z'
        ]
        chart.raphael.path(path.join(' '))
          .attr('stroke', 'transparent')
          .attr('fill', color);
      }
    })

    // Already rendered.
    chart.options.isRenderedDiff = true;
  }

})(jQuery, Drupal, once);
