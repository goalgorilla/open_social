<?php

namespace Drupal\activity_send\Annotation;

use Drupal\Core\Annotation\Translation;
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
