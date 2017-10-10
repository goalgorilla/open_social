<?php

namespace Drupal\activity_send_email\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an EmailFrequency Annotation object.
 *
 * @Annotation
 */
class EmailFrequency extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the flavor.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The frequency.
   *
   * @var integer
   */
  public $interval;

}
