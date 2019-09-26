<?php

namespace Drupal\activity_send_push_notification\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Push Annotation object.
 *
 * @Annotation
 */
class Push extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of form elements set.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
