<?php

namespace Drupal\activity_creator\Annotation;

use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Activity destination item annotation object.
 *
 * @see \Drupal\activity_creator\Plugin\ActivityDestinationManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActivityDestination extends Plugin {

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
   * Whether this destination supports aggregation or not.
   *
   * @var bool
   */
  public bool $isAggregatable = FALSE;

  /**
   * Whether this destination is common or not.
   *
   * @var bool
   */
  public bool $isCommon = FALSE;

}
