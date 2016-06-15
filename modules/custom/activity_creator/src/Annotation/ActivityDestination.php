<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Annotation\ActivityDestination.
 */

namespace Drupal\activity_creator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Activity destination item annotation object.
 *
 * @see \Drupal\activity_creator\Plugin\ActivityDestinationManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActivityDestination extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
