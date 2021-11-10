<?php

namespace Drupal\activity_creator\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Activity action item annotation object.
 *
 * @see \Drupal\activity_creator\Plugin\ActivityActionManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActivityAction extends Plugin {

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

}
