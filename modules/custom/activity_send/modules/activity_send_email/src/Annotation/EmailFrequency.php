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
   * The weight of the frequency option.
   *
   * @var int
   */
  public $weight;

  /**
   * The send interval of the email frequency in seconds.
   *
   * @var int
   */
  public $interval;

}
