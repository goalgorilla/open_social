<?php

namespace Drupal\kpi_analytics\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a KPI Datasource item annotation object.
 *
 * @see \Drupal\kpi_analytics\Plugin\KPIDatasourceManager
 * @see plugin_api
 *
 * @Annotation
 */
class KPIDatasource extends Plugin {

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
