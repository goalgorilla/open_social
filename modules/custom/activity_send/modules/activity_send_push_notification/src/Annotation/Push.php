<?php

namespace Drupal\activity_send_push_notification\Annotation;

use Drupal\Core\Annotation\Translation;
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
  public string $id;

  /**
   * The title of form elements set.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $title;

  /**
   * The description of form elements set.
   *
   * @var \Drupal\Core\Annotation\Translation|null
   *
   * @ingroup plugin_translatable
   */
  public ?Translation $description = NULL;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public int $weight = 0;

}
