<?php

namespace Drupal\kpi_analytics\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a KPI Data Formatter item annotation object.
 *
 * @see \Drupal\kpi_analytics\Plugin\KPIDataFormatterManager
 * @see plugin_api
 *
 * @Annotation
 */
class KPIDataFormatter extends Plugin {

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
