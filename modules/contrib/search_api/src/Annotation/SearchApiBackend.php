<?php

namespace Drupal\search_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Search API backend annotation object.
 *
 * @see \Drupal\search_api\Backend\BackendPluginManager
 * @see \Drupal\search_api\Backend\BackendInterface
 * @see \Drupal\search_api\Backend\BackendPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class SearchApiBackend extends Plugin {

  /**
   * The backend plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the backend plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The backend description.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
