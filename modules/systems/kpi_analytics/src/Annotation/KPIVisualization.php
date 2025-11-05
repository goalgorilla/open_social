<?php

namespace Drupal\kpi_analytics\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a KPI Visualization item annotation object.
 *
 * @see \Drupal\kpi_analytics\Plugin\KPIVisualizationManager
 * @see plugin_api
 *
 * @Annotation
 */
class KPIVisualization extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The label of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

}
