<?php

/**
 * @file
 * Contains \Drupal\activity_send\Annotation\ActivitySend.
 */

namespace Drupal\activity_send\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an ActivitySend item annotation object.
 *
 * @see \Drupal\activity_send\Plugin\ActivitySendManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActivitySend extends Plugin {

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
