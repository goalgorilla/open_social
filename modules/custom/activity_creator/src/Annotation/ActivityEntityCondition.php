<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Annotation\ActivityEntityCondition.
 */

namespace Drupal\activity_creator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Activity entity condition item annotation object.
 *
 * @see \Drupal\activity_creator\Plugin\ActivityEntityConditionManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActivityEntityCondition extends Plugin {

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

  /**
   * The array with entities for which this plugin is allowed.
   *
   * @var array (optional)
   */
  public $entities;

}
