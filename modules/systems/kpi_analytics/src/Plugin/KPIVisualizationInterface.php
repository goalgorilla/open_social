<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for KPI Visualization plugins.
 */
interface KPIVisualizationInterface extends PluginInspectionInterface {

  /**
   * Render the data.
   *
   * @param array $data
   *   Data to render.
   *
   * @return array
   *   Render array.
   */
  public function render(array $data);

  /**
   * Set a list with labels for chart.
   *
   * @param array $labels
   *   Array where each value is a label.
   *
   * @return \Drupal\kpi_analytics\Plugin\KPIVisualizationInterface
   *   The KPIVisualizationInterface object.
   */
  public function setLabels(array $labels);

  /**
   * Set a list with colors for chart.
   *
   * @param array $colors
   *   Array where each value is hex code.
   *
   * @return \Drupal\kpi_analytics\Plugin\KPIVisualizationInterface
   *   The KPIVisualizationInterface object.
   */
  public function setColors(array $colors);

}
