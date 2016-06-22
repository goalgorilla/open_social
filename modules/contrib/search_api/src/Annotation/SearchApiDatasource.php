<?php

namespace Drupal\search_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Search API datasource annotation object.
 *
 * @see \Drupal\search_api\Datasource\DatasourcePluginManager
 * @see \Drupal\search_api\Datasource\DatasourceInterface
 * @see \Drupal\search_api\Datasource\DatasourcePluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class SearchApiDatasource extends Plugin {

  /**
   * The datasource plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the datasource plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the datasource.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  // @todo Use or remove.
  public $description;

}
