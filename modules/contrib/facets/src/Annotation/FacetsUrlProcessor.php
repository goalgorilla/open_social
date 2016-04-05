<?php

namespace Drupal\facets\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Facets URL Processor annotation.
 *
 * @see \Drupal\facets\Processor\ProcessorPluginManager
 * @see plugin_api
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FacetsUrlProcessor extends Plugin {

  /**
   * The URL processor plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the URL processor plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The URL processor description.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
