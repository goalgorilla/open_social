<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Annotation\ActiviyContext.
 */

namespace Drupal\activity_creator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Activity context plugin item annotation object.
 *
 * @see \Drupal\activity_creator\Plugin\ActivityContextManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActivityContext extends Plugin {

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
