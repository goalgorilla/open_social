<?php

namespace Drupal\activity_send_email\Annotation;

use Drupal\Core\Annotation\Translation;
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
  public string $id;

  /**
   * The name of the flavor.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $name;

  /**
   * The weight of the frequency option.
   *
   * @var int
   */
  public int $weight;

  /**
   * The send interval of the email frequency in seconds.
   *
   * @var int
   */
  public int $interval;

}
