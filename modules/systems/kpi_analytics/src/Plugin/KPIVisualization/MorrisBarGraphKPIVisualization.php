<?php

namespace Drupal\kpi_analytics\Plugin\KPIVisualization;

use Drupal\kpi_analytics\Plugin\KPIVisualizationBase;

/**
 * Provides a 'MorrisBarGraphKPIVisualization' KPI Visualization.
 *
 * @KPIVisualization(
 *  id = "morris_bar_graph_kpi_visualization",
 *  label = @Translation("Morris bar graph KPI visualization"),
 * )
 */
class MorrisBarGraphKPIVisualization extends KPIVisualizationBase {

  /**
   * {@inheritdoc}
   */
  public function render(array $data): array {
    $uuid = $this->uuid->generate();

    $xkey = 'x';
    $ykeys = ['y'];

    if (count($data) > 0) {
      $ykeys = [];

      foreach (reset($data) as $key => $value) {
        $ykeys[] = $key;
      }

      $xkey = array_shift($ykeys);
    }

    // Data to render and Morris options.
    $options = [
      'element' => $uuid,
      'data' => $data,
      'xkey' => $xkey,
      'ykeys' => $ykeys,
      'parseTime' => FALSE,
      'labels' => $this->labels,
      'plugin' => 'Bar',
      'barColors' => $this->colors,
      'stacked' => TRUE,
      'dataLabels' => FALSE,
    ];

    return [
      '#theme' => 'kpi_analytics_morris_chart',
      '#type' => 'bar',
      '#uuid' => $uuid,
      '#labels' => $this->labels,
      '#colors' => $this->colors,
      '#attached' => [
        'library' => [
          'kpi_analytics/morris',
        ],
        'drupalSettings' => [
          'kpi_analytics' => [
            'morris' => [
              'chart' => [
                $uuid => [
                  'options' => $options,
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
