<?php

namespace Drupal\kpi_analytics\Plugin\KPIVisualization;

use Drupal\kpi_analytics\Plugin\KPIVisualizationBase;

/**
 * Provides a 'SimpleTextKPIVisualization' KPI Visualization.
 *
 * @KPIVisualization(
 *  id = "simple_text_kpi_visualization",
 *  label = @Translation("Simple text KPI visualization"),
 * )
 */
class SimpleTextKPIVisualization extends KPIVisualizationBase {

  /**
   * {@inheritdoc}
   */
  public function render(array $data): array {
    $render_array = [];
    $value = $data[0];
    $render_array['kpi_analytics']['#markup'] = array_shift($value) . ' registered users on the platform';
    return $render_array;
  }

}
