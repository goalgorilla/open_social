<?php

namespace Drupal\activity_creator\Annotation;

use Drupal\Core\Annotation\Translation;
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
  public string $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * The array with entities for which this plugin is allowed.
   *
   * @var array
   */
  public array $entities;

}
